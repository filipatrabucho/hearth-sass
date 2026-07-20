import { useEffect, useState, useCallback } from 'react'
import { supabase } from '../../lib/supabaseClient'
import { useAuth } from '../../contexts/AuthContext'
import styles from './AdminCrud.module.css'

const EMPTY_FORM = { title: '', body: '' }

export function Updates() {
  const { currentWorkspace, user } = useAuth()
  const [entries, setEntries] = useState([])
  const [loading, setLoading] = useState(true)
  const [form, setForm] = useState(EMPTY_FORM)
  const [submitting, setSubmitting] = useState(false)
  const [feedback, setFeedback] = useState(null)

  const load = useCallback(async () => {
    if (!currentWorkspace) return
    setLoading(true)
    const { data } = await supabase
      .from('changelog_entries')
      .select('*')
      .eq('workspace_id', currentWorkspace.id)
      .order('published_at', { ascending: false })
    setEntries(data || [])
    setLoading(false)
  }, [currentWorkspace])

  useEffect(() => { load() }, [load])

  async function handleSubmit(e) {
    e.preventDefault()
    if (!currentWorkspace) return
    setSubmitting(true)
    setFeedback(null)
    try {
      const { error } = await supabase.from('changelog_entries').insert({
        workspace_id: currentWorkspace.id,
        title: form.title.trim(),
        body: form.body.trim(),
        created_by: user.id,
      })
      if (error) throw error
      setForm(EMPTY_FORM)
      setFeedback({ type: 'ok', text: 'Atualização publicada.' })
      load()
    } catch (err) {
      setFeedback({ type: 'err', text: err.message || 'Não foi possível publicar.' })
    } finally {
      setSubmitting(false)
    }
  }

  async function handleDelete(id) {
    await supabase.from('changelog_entries').delete().eq('id', id)
    load()
  }

  return (
    <div>
      <h1 className="page-title">Updates</h1>
      <p className="page-sub">Publica uma atualização — aparece automaticamente em /updates.</p>

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
        <div className={`${styles.field} ${styles.fieldWide}`}>
          <label className={styles.label} htmlFor="body">Descrição</label>
          <textarea
            id="body"
            className={styles.textarea}
            rows={4}
            required
            value={form.body}
            onChange={(e) => setForm((f) => ({ ...f, body: e.target.value }))}
          />
        </div>
        <div className={styles.formActions}>
          <button type="submit" className="btn-primary" disabled={submitting}>
            {submitting ? 'A publicar…' : 'Publicar'}
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
            <tr><th>Título</th><th>Publicado em</th><th></th></tr>
          </thead>
          <tbody>
            {entries.map((e) => (
              <tr key={e.id}>
                <td>{e.title}</td>
                <td>{new Date(e.published_at).toLocaleDateString('pt-PT')}</td>
                <td><button className={styles.deleteBtn} onClick={() => handleDelete(e.id)}>Eliminar</button></td>
              </tr>
            ))}
          </tbody>
        </table>
      )}
    </div>
  )
}
