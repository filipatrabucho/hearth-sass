import { useEffect, useState } from 'react'
import { supabase } from '../../lib/supabaseClient'
import { usePublicWorkspace } from '../../contexts/PublicWorkspaceContext'
import styles from './Updates.module.css'

function formatDate(iso) {
  return new Date(iso).toLocaleDateString('pt-PT', { day: '2-digit', month: 'long', year: 'numeric' })
}

export function Updates() {
  const { workspace } = usePublicWorkspace()
  const [entries, setEntries] = useState([])
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    if (!workspace) return
    let cancelled = false

    supabase
      .from('changelog_entries')
      .select('*')
      .eq('workspace_id', workspace.id)
      .order('published_at', { ascending: false })
      .then(({ data }) => {
        if (cancelled) return
        setEntries(data || [])
        setLoading(false)
      })

    return () => { cancelled = true }
  }, [workspace])

  return (
    <div className={styles.wrap}>
      <span className="page-label">Changelog</span>
      <h1 className="page-title">Updates</h1>
      <p className="page-sub">O histórico de mudanças {workspace?.name ? `de ${workspace.name}` : 'desta comunidade'}.</p>

      {loading ? (
        <p style={{ color: 'var(--text-muted)', marginTop: 24 }}>A carregar…</p>
      ) : entries.length === 0 ? (
        <p style={{ color: 'var(--text-muted)', marginTop: 24 }}>Ainda não há atualizações publicadas.</p>
      ) : (
        <div className={styles.timeline}>
          {entries.map((entry) => (
            <article key={entry.id} className={styles.entry}>
              <span className={styles.date}>{formatDate(entry.published_at)}</span>
              <h2>{entry.title}</h2>
              <p>{entry.body}</p>
            </article>
          ))}
        </div>
      )}
    </div>
  )
}
