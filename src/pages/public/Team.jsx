import { useEffect, useState } from 'react'
import { supabase } from '../../lib/supabaseClient'
import { usePublicWorkspace } from '../../contexts/PublicWorkspaceContext'
import styles from './Team.module.css'

const ROLE_LABELS = { owner: 'Fundador', admin: 'Admin', staff: 'Staff' }

export function Team() {
  const { workspace } = usePublicWorkspace()
  const [members, setMembers] = useState([])
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    if (!workspace) return
    let cancelled = false

    supabase
      .from('team_page_members')
      .select('*')
      .eq('workspace_id', workspace.id)
      .order('role', { ascending: true })
      .then(({ data }) => {
        if (cancelled) return
        setMembers(data || [])
        setLoading(false)
      })

    return () => { cancelled = true }
  }, [workspace])

  return (
    <div className={styles.wrap}>
      <span className="page-label">Comunidade</span>
      <h1 className="page-title">Equipa</h1>
      <p className="page-sub">As pessoas que mantêm {workspace?.name || 'a comunidade'} a funcionar todos os dias.</p>

      {loading ? (
        <p style={{ color: 'var(--text-muted)', marginTop: 24 }}>A carregar equipa…</p>
      ) : members.length === 0 ? (
        <p style={{ color: 'var(--text-muted)', marginTop: 24 }}>
          Ainda não há membros da staff visíveis nesta página.
        </p>
      ) : (
        <div className={styles.grid}>
          {members.map((m) => (
            <div key={`${m.workspace_id}-${m.display_name}-${m.joined_at}`} className={styles.card}>
              <div className={styles.avatar}>
                {m.avatar_url ? (
                  <img src={m.avatar_url} alt="" />
                ) : (
                  (m.display_name || '?').slice(0, 2).toUpperCase()
                )}
              </div>
              <h3>{m.display_name || 'Membro da staff'}</h3>
              <span className={styles.role}>{m.public_title || ROLE_LABELS[m.role] || 'Staff'}</span>
              {m.bio && <p className={styles.bio}>{m.bio}</p>}
            </div>
          ))}
        </div>
      )}
    </div>
  )
}
