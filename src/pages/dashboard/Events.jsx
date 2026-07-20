import { useEffect, useState, useCallback } from 'react'
import { supabase } from '../../lib/supabaseClient'
import { useAuth } from '../../contexts/AuthContext'
import styles from './AdminCrud.module.css'

const EMPTY_FORM = { title: '', description: '', location: '', starts_at: '', ends_at: '' }

export function Events() {
  const { currentWorkspace, user } = useAuth()
  const [events, setEvents] = useState([])
  const [loading, setLoading] = useState(true)
  const [form, setForm] = useState(EMPTY_FORM)
  const [submitting, setSubmitting] = useState(false)
  const [feedback, setFeedback] = useState(null)

  const load = useCallback(async () => {
    if (!currentWorkspace) return
    setLoading(true)
    const { data } = await supabase
      .from('events')
      .select('*')
      .eq('workspace_id', currentWorkspace.id)
      .order('starts_at', { ascending: false })
    setEvents(data || [])
    setLoading(false)
  }, [currentWorkspace])

  useEffect(() => { load() }, [load])

  async function handleSubmit(e) {
    e.preventDefault()
    if (!currentWorkspace) return
    setSubmitting(true)
    setFeedback(null)
    try {
      const { error } = await supabase.from('events').insert({
        workspace_id: currentWorkspace.id,
        title: form.title.trim(),
        description: form.description.trim() || null,
        location: form.location.trim() || null,
        starts_at: new Date(form.starts_at).toISOString(),
        ends_at: form.ends_at ? new Date(form.ends_at).toISOString() : null,
        created_by: user.id,
      })
      if (error) throw error
      setForm(EMPTY_FORM)
      setFeedback({ type: 'ok', text: 'Evento criado.' })
      load()
    } catch (err) {
      setFeedback({ type: 'err', text: err.message || 'Não foi possível criar o evento.' })
    } finally {
      setSubmitting(false)
    }
  }

  async function handleDelete(id) {
    await supabase.from('events').delete().eq('id', id)
    load()
  }

  return (
    <div>
      <h1 className="page-title">Eventos</h1>
      <p className="page-sub">Cria eventos — aparecem automaticamente em /eventos e na homepage.</p>

      <form className={styles.form} onSubmit={handleSubmit}>
        <div className={`${styles.field} ${styles.fieldWide}`}>
          <label className={styles.label} htmlFor="title">Título</label>
          <input
            id="title"
            className={styles.input}
            required
            value={form.title}
            onChange={(e) => setForm((f) => ({ ...f, title: e.target.value }))}
          />
        </div>

        <div className={styles.field}>
          <label className={styles.label} htmlFor="starts_at">Início</label>
          <input
            id="starts_at"
            type="datetime-local"
            className={styles.input}
            required
            value={form.starts_at}
            onChange={(e) => setForm((f) => ({ ...f, starts_at: e.target.value }))}
          />
        </div>
        <div className={styles.field}>
          <label className={styles.label} htmlFor="ends_at">Fim (opcional)</label>
          <input
            id="ends_at"
            type="datetime-local"
            className={styles.input}
            value={form.ends_at}
            onChange={(e) => setForm((f) => ({ ...f, ends_at: e.target.value }))}
          />
        </div>

        <div className={styles.field}>
          <label className={styles.label} htmlFor="location">Localização</label>
          <input
            id="location"
            className={styles.input}
            placeholder="Canal de voz, link externo…"
            value={form.location}
            onChange={(e) => setForm((f) => ({ ...f, location: e.target.value }))}
          />
        </div>
        <div className={`${styles.field} ${styles.fieldWide}`}>
          <label className={styles.label} htmlFor="description">Descrição</label>
          <textarea
            id="description"
            className={styles.textarea}
            rows={3}
            value={form.description}
            onChange={(e) => setForm((f) => ({ ...f, description: e.target.value }))}
          />
        </div>

        <div className={styles.formActions}>
          <button type="submit" className="btn-primary" disabled={submitting}>
            {submitting ? 'A criar…' : 'Criar evento'}
          </button>
        </div>
      </form>

      {feedback && (
        <p className={`${styles.feedback} ${feedback.type === 'ok' ? styles.ok : styles.err}`}>{feedback.text}</p>
      )}

      {loading ? (
        <p style={{ color: 'var(--text-muted)' }}>A carregar…</p>
      ) : (
        <table className={styles.table}>
          <thead>
            <tr><th>Título</th><th>Início</th><th>Localização</th><th></th></tr>
          </thead>
          <tbody>
            {events.map((ev) => (
              <tr key={ev.id}>
                <td>{ev.title}</td>
                <td>{new Date(ev.starts_at).toLocaleString('pt-PT')}</td>
                <td>{ev.location || '—'}</td>
                <td><button className={styles.deleteBtn} onClick={() => handleDelete(ev.id)}>Eliminar</button></td>
              </tr>
            ))}
          </tbody>
        </table>
      )}
    </div>
  )
}
