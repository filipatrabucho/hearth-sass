import { createContext, useContext, useEffect, useState, useCallback } from 'react'
import { supabase } from '../lib/supabaseClient'

const AuthContext = createContext(null)

const CURRENT_WORKSPACE_KEY = 'hearth_current_workspace_id'

export function AuthProvider({ children }) {
  const [session, setSession] = useState(null)
  const [profile, setProfile] = useState(null)
  const [workspaces, setWorkspaces] = useState([])
  const [currentWorkspaceId, setCurrentWorkspaceIdState] = useState(
    () => localStorage.getItem(CURRENT_WORKSPACE_KEY) || null
  )

  // authLoading: ainda não sabemos se há sessão.
  // dataLoading: há sessão, mas ainda estamos a buscar profile/workspaces.
  const [authLoading, setAuthLoading] = useState(true)
  const [dataLoading, setDataLoading] = useState(false)

  const loadProfileAndWorkspaces = useCallback(async (user) => {
    if (!user) {
      setProfile(null)
      setWorkspaces([])
      return
    }
    setDataLoading(true)
    try {
      const { data: profileRow } = await supabase
        .from('profiles')
        .select('*')
        .eq('id', user.id)
        .maybeSingle()
      setProfile(profileRow || null)

      const { data: memberships } = await supabase
        .from('workspace_members')
        .select('role, workspace:workspaces(id, name, plan, discord_guild_id)')
        .eq('profile_id', user.id)
        .eq('status', 'active')

      const ws = (memberships || [])
        .filter((m) => m.workspace)
        .map((m) => ({ ...m.workspace, myRole: m.role }))
      setWorkspaces(ws)

      const saved = localStorage.getItem(CURRENT_WORKSPACE_KEY)
      const savedIsValid = ws.some((w) => w.id === saved)
      if (!savedIsValid) {
        const fallback = ws[0]?.id || null
        setCurrentWorkspaceIdState(fallback)
        if (fallback) localStorage.setItem(CURRENT_WORKSPACE_KEY, fallback)
      } else {
        setCurrentWorkspaceIdState(saved)
      }
    } finally {
      setDataLoading(false)
    }
  }, [])

  useEffect(() => {
    supabase.auth.getSession().then(({ data: { session: s } }) => {
      setSession(s)
      setAuthLoading(false)
      if (s?.user) loadProfileAndWorkspaces(s.user)
    })

    const { data: sub } = supabase.auth.onAuthStateChange((_event, s) => {
      setSession(s)
      setAuthLoading(false)
      if (s?.user) {
        loadProfileAndWorkspaces(s.user)
      } else {
        setProfile(null)
        setWorkspaces([])
      }
    })

    return () => sub.subscription.unsubscribe()
  }, [loadProfileAndWorkspaces])

  function setCurrentWorkspaceId(id) {
    setCurrentWorkspaceIdState(id)
    if (id) localStorage.setItem(CURRENT_WORKSPACE_KEY, id)
  }

  async function signInWithDiscord() {
    await supabase.auth.signInWithOAuth({
      provider: 'discord',
      options: { redirectTo: `${window.location.origin}/auth/callback` },
    })
  }

  async function signOut() {
    await supabase.auth.signOut()
    localStorage.removeItem(CURRENT_WORKSPACE_KEY)
  }

  async function callFunction(name, body) {
    const token = session?.access_token
    const res = await fetch(`/.netlify/functions/${name}`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        ...(token ? { Authorization: `Bearer ${token}` } : {}),
      },
      body: JSON.stringify(body),
    })
    const json = await res.json().catch(() => ({}))
    if (!res.ok) throw new Error(json.error || 'Erro no servidor.')
    return json
  }

  async function createWorkspace({ name, discordGuildId }) {
    const { workspace } = await callFunction('create-workspace', { name, discordGuildId })
    await loadProfileAndWorkspaces(session.user)
    setCurrentWorkspaceId(workspace.id)
    return workspace
  }

  async function inviteStaff({ workspaceId, email, role }) {
    return callFunction('invite-staff', { workspaceId, email, role })
  }

  const currentWorkspace = workspaces.find((w) => w.id === currentWorkspaceId) || null

  const value = {
    session,
    user: session?.user || null,
    profile,
    workspaces,
    currentWorkspace,
    setCurrentWorkspaceId,
    authLoading,
    dataLoading,
    signInWithDiscord,
    signOut,
    createWorkspace,
    inviteStaff,
    refreshWorkspaces: () => session?.user && loadProfileAndWorkspaces(session.user),
  }

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>
}

export function useAuth() {
  const ctx = useContext(AuthContext)
  if (!ctx) throw new Error('useAuth tem de ser usado dentro de <AuthProvider>')
  return ctx
}
