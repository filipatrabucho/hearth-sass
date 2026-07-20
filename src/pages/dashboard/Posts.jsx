import { useEffect, useState, useCallback } from 'react'
import { supabase } from '../../lib/supabaseClient'
import { useAuth } from '../../contexts/AuthContext'
import styles from './AdminCrud.module.css'

const EMPTY_FORM = { type: 'post', title: '', body: '', image_url: '', link_url: '', show_on_homepage: false }

export function Posts() {
  const { currentWorkspace, user } = useAuth()
  const [items, setItems] = useState([])
  const [loading, setLoading] = useState(true)
  const [form, setForm] = useState(EMPTY_FORM)
  const [submitting, setSubmitting] = useState(false)
  const [feedback, setFeedback] = useState(null)

  const load = useCallback(async () => {
    if (!currentWorkspace) return
    setLoading(true)
    const { data } = await supabase
      .from('posts')
      .select('*')
      .eq('workspace_id', currentWorkspace.id)
      .order('created_at', { ascending: false })
    setItems(data || [])
    setLoading(false)
  }, [currentWorkspace])

  useEffect(() => { load() }, [load])

  async function handleSubmit(e) {
    e.preventDefault()
    if (!currentWorkspace) return
    setSubmitting(true)
    setFeedback(null)
    try {
      const { error } = await supabase.from('posts').insert({
        workspace_id: currentWorkspace.id,
        type: form.type,
        title: form.title.trim(),
        body: form.body.trim() || null,
        image_url: form.image_url.trim() || null,
        link_url: form.link_url.trim() || null,
        show_on_homepage: form.show_on_homepage,
        created_by: user.id,
      })
      if (error) throw error
      setForm(EMPTY_FORM)
      setFeedback({ type: 'ok', text: 'Publicação criada.' })
      load()
    } catch (err) {
      setFeedback({ type: 'err', text: err.message || 'Não foi possível criar.' })
    } finally {
      setSubmitting(false)
    }
  }

  async function toggleHomepage(item) {
    await supabase.from('posts').update({ show_on_homepage: !item.show_on_homepage }).eq('id', item.id)
    load()
  }

  async function handleDelete(id) {
    await supabase.from('posts').delete().eq('id', id)
    load()
  }

  return (
    <div>
      <h1 className="page-title">Publicações & Parceiros</h1>
      <p className="page-sub">
        Cria posts de novidades ou parceiros. Marca "mostrar na homepage" para aparecerem no site público.
      </p>

      <form className={styles.form} onSubmit={handleSubmit}>
        <div className={styles.field}>
          <label className={styles.label} htmlFor="type">Tipo</label>
          <select
            id="type"
            className={styles.select}
            value={form.type}
            onChange={(e) => setForm((f) => ({ ...f, type: e.target.value }))}
          >
            <option value="post">Publicação</option>
            <option value="partner">Parceiro</option>
          </select>
        </div>
        <div className={styles.field}>
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
            rows={3}
            value={form.body}
            onChange={(e) => setForm((f) => ({ ...f, body: e.target.value }))}
          />
        </div>

        <div className={styles.field}>
          <label className={styles.label} htmlFor="image">URL da imagem/logo</label>
          <input
            id="image"
            className={styles.input}
            value={form.image_url}
            onChange={(e) => setForm((f) => ({ ...f, image_url: e.target.value }))}
          />
        </div>
        <div className={styles.field}>
          <label className={styles.label} htmlFor="link">Link (opcional)</label>
          <input
            id="link"
            className={styles.input}
            value={form.link_url}
            onChange={(e) => setForm((f) => ({ ...f, link_url: e.target.value }))}
          />
        </div>

        <div className={`${styles.checkboxRow} ${styles.fieldWide}`}>
          <input
            id="show"
            type="checkbox"
            checked={form.show_on_homepage}
            onChange={(e) => setForm((f) => ({ ...f, show_on_homepage: e.target.checked }))}
          />
          <label htmlFor="show">Mostrar na homepage</label>
        </div>

        <div className={styles.formActions}>
          <button type="submit" className="btn-primary" disabled={submitting}>
            {submitting ? 'A criar…' : 'Criar'}
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
            <tr><th>Título</th><th>Tipo</th><th>Homepage</th><th></th></tr>
          </thead>
          <tbody>
            {items.map((item) => (
              <tr key={item.id}>
                <td>{item.title}</td>
                <td><span className={styles.pill}>{item.type === 'partner' ? 'Parceiro' : 'Publicação'}</span></td>
                <td>
                  <button className="btn-secondary" style={{ padding: '4px 10px', fontSize: 12 }} onClick={() => toggleHomepage(item)}>
                    {item.show_on_homepage ? 'Visível' : 'Oculto'}
                  </button>
                </td>
                <td><button className={styles.deleteBtn} onClick={() => handleDelete(item.id)}>Eliminar</button></td>
              </tr>
            ))}
          </tbody>
        </table>
      )}
    </div>
  )
}
