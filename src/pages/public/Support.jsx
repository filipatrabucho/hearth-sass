import { useState } from 'react'
import { usePublicWorkspace } from '../../contexts/PublicWorkspaceContext'
import styles from './Support.module.css'

export function Support() {
  const { workspace } = usePublicWorkspace()
  const [form, setForm] = useState({ name: '', email: '', subject: '', message: '' })
  const [submitting, setSubmitting] = useState(false)
  const [feedback, setFeedback] = useState(null)

  function update(field) {
    return (e) => setForm((f) => ({ ...f, [field]: e.target.value }))
  }

  async function handleSubmit(e) {
    e.preventDefault()
    if (!workspace) return
    setSubmitting(true)
    setFeedback(null)
    try {
      const res = await fetch('/.netlify/functions/contact-support', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ workspaceId: workspace.id, ...form }),
      })
      const json = await res.json().catch(() => ({}))
      if (!res.ok) throw new Error(json.error || 'Não foi possível enviar a mensagem.')
      setFeedback({ type: 'ok', text: 'Mensagem enviada! A equipa vai responder em breve.' })
      setForm({ name: '', email: '', subject: '', message: '' })
    } catch (err) {
      setFeedback({ type: 'err', text: err.message })
    } finally {
      setSubmitting(false)
    }
  }

  return (
    <div className={styles.wrap}>
      <span className="page-label">Ajuda</span>
      <h1 className="page-title">Suporte</h1>
      <p className="page-sub">Tens uma dúvida, problema ou sugestão? Escreve-nos.</p>

      <form className={styles.form} onSubmit={handleSubmit}>
        <div className={styles.row}>
          <div className={styles.field}>
            <label className={styles.label} htmlFor="name">Nome</label>
            <input id="name" className={styles.input} required value={form.name} onChange={update('name')} />
          </div>
          <div className={styles.field}>
            <label className={styles.label} htmlFor="email">Email</label>
            <input id="email" type="email" className={styles.input} required value={form.email} onChange={update('email')} />
          </div>
        </div>

        <div className={styles.field}>
          <label className={styles.label} htmlFor="subject">Assunto</label>
          <input id="subject" className={styles.input} value={form.subject} onChange={update('subject')} />
        </div>

        <div className={styles.field}>
          <label className={styles.label} htmlFor="message">Mensagem</label>
          <textarea id="message" className={styles.textarea} rows={6} required value={form.message} onChange={update('message')} />
        </div>

        <button type="submit" className="btn-primary" disabled={submitting || !workspace}>
          {submitting ? 'A enviar…' : 'Enviar mensagem'}
        </button>

        {feedback && (
          <p className={feedback.type === 'ok' ? styles.ok : styles.err}>{feedback.text}</p>
        )}
      </form>
    </div>
  )
}
