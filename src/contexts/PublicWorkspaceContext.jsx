import { createContext, useContext, useEffect, useState } from 'react'
import { supabase } from '../lib/supabaseClient'
import { applyWorkspaceTheme } from '../lib/theme'

const PublicWorkspaceContext = createContext(null)

// Enquanto não há subdomain routing real (slug.hearth.gg — Fase 1), o site
// público mostra o workspace indicado em VITE_PUBLIC_WORKSPACE_SLUG, ou o
// primeiro workspace criado se a variável não estiver definida.
export function PublicWorkspaceProvider({ children }) {
  const [workspace, setWorkspace] = useState(null)
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    let cancelled = false

    async function load() {
      const slug = import.meta.env.VITE_PUBLIC_WORKSPACE_SLUG
      let query = supabase.from('workspaces').select('*').limit(1)
      query = slug ? query.eq('slug', slug) : query.order('created_at', { ascending: true })

      const { data, error } = await query.maybeSingle()
      if (cancelled) return
      if (!error && data) {
        setWorkspace(data)
        applyWorkspaceTheme(data)
      } else {
        applyWorkspaceTheme(null)
      }
      setLoading(false)
    }

    load()
    return () => { cancelled = true }
  }, [])

  return (
    <PublicWorkspaceContext.Provider value={{ workspace, loading }}>
      {children}
    </PublicWorkspaceContext.Provider>
  )
}

export function usePublicWorkspace() {
  const ctx = useContext(PublicWorkspaceContext)
  if (!ctx) throw new Error('usePublicWorkspace tem de ser usado dentro de <PublicWorkspaceProvider>')
  return ctx
}
