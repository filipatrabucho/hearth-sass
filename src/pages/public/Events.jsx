import { useEffect, useState } from 'react'
import { supabase } from '../../lib/supabaseClient'
import { usePublicWorkspace } from '../../contexts/PublicWorkspaceContext'
import styles from './Events.module.css'

function formatDate(iso) {
  return new Date(iso).toLocaleDateString('pt-PT', {
    weekday: 'short', day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit',
  })
}

export function Events() {
  const { workspace } = usePublicWorkspace()
  const [events, setEvents] = useState([])
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    if (!workspace) return
    let cancelled = false

    supabase
      .from('events')
      .select('*')
      .eq('workspace_id', workspace.id)
      .order('starts_at', { ascending: false })
      .then(({ data }) => {
        if (cancelled) return
        setEvents(data || [])
        setLoading(false)
      })

    return () => { cancelled = true }
  }, [workspace])

  const now = Date.now()
  const upcoming = events.filter((e) => new Date(e.starts_at).getTime() >= now).reverse()
  const past = events.filter((e) => new Date(e.starts_at).getTime() < now)

  return (
    <div className={styles.wrap}>
      <span className="page-label">Calendário</span>
      <h1 className="page-title">Eventos</h1>
      <p className="page-sub">Tudo o que vai acontecer — e o que já aconteceu — {workspace?.name ? `em ${workspace.name}` : 'nesta comunidade'}.</p>

      {loading ? (
        <p style={{ color: 'var(--text-muted)', marginTop: 24 }}>A carregar…</p>
      ) : (
        <>
          <section className={styles.section}>
            <h2 className={styles.sectionTitle}>Próximos</h2>
            {upcoming.length === 0 ? (
              <p style={{ color: 'var(--text-muted)' }}>Sem eventos marcados por agora.</p>
            ) : (
              <div className={styles.list}>
                {upcoming.map((ev) => (
                  <div key={ev.id} className={styles.card}>
                    <span className={styles.date}>{formatDate(ev.starts_at)}</span>
                    <h3>{ev.title}</h3>
                    {ev.description && <p>{ev.description}</p>}
                    {ev.location && <span className={styles.location}>📍 {ev.location}</span>}
                  </div>
                ))}
              </div>
            )}
          </section>

          {past.length > 0 && (
            <section className={styles.section}>
              <h2 className={styles.sectionTitle}>Anteriores</h2>
              <div className={styles.list}>
                {past.map((ev) => (
                  <div key={ev.id} className={`${styles.card} ${styles.pastCard}`}>
                    <span className={styles.date}>{formatDate(ev.starts_at)}</span>
                    <h3>{ev.title}</h3>
                  </div>
                ))}
              </div>
            </section>
          )}
        </>
      )}
    </div>
  )
}
