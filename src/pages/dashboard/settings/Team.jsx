import { useEffect, useState, useCallback } from 'react'
import { supabase } from '../../../lib/supabaseClient'
import { useAuth } from '../../../contexts/AuthContext'
import styles from './Team.module.css'

export function Team() {
  const { currentWorkspace, inviteStaff } = useAuth()
  const [members, setMembers] = useState([])
  const [loading, setLoading] = useState(true)

  const [email, setEmail] = useState('')
  const [role, setRole] = useState('staff')
  const [submitting, setSubmitting] = useState(false)
  const [feedback, setFeedback] = useState(null) // { type: 'ok' | 'err', text }

  const loadMembers = useCallback(async () => {
    if (!currentWorkspace) return
    setLoading(true)
    const { data } = await supabase
      .from('workspace_members')
      .select('id, email, role, status, invited_at, joined_at')
      .eq('workspace_id', currentWorkspace.id)
      .order('invited_at', { ascending: true })
    setMembers(data || [])
    setLoading(false)
  }, [currentWorkspace])

  useEffect(() => { loadMembers() }, [loadMembers])

  async function handleInvite(e) {
    e.preventDefault()
    if (!currentWorkspace) return
    setSubmitting(true)
    setFeedback(null)
    try {
      await inviteStaff({ workspaceId: currentWorkspace.id, email, role })
      setFeedback({ type: 'ok', text: `Convite enviado para ${email}.` })
      setEmail('')
      loadMembers()
    } catch (err) {
      setFeedback({ type: 'err', text: err.message || 'Não foi possível enviar o convite.' })
    } finally {
      setSubmitting(false)
    }
  }

  return (
    <div>
      <h1 className="page-title">Equipa</h1>
      <p className="page-sub">Convida staff para geres o workspace "{currentWorkspace?.name}" contigo.</p>

      <form className={styles.inviteForm} onSubmit={handleInvite}>
        <div className={styles.field}>
          <label className={styles.label} htmlFor="invite-email">Email</label>
          <input
            id="invite-email"
            type="email"
            required
            className={styles.input}
            placeholder="staff@exemplo.com"
            value={email}
            onChange={(e) => setEmail(e.target.value)}
          />
        </div>

        <div>
          <label className={styles.label} htmlFor="invite-role">Cargo</label>
          <select
            id="invite-role"
            className={styles.select}
            value={role}
            onChange={(e) => setRole(e.target.value)}
          >
            <option value="staff">Staff</option>
            <option value="admin">Admin</option>
          </select>
        </div>

        <button type="submit" className="btn-primary" disabled={submitting}>
          {submitting ? 'A enviar…' : 'Convidar'}
        </button>
      </form>

      {feedback && (
        <p className={`${styles.feedback} ${feedback.type === 'ok' ? styles.ok : styles.err}`}>
          {feedback.text}
        </p>
      )}

      {loading ? (
        <p style={{ color: 'var(--text-muted)' }}>A carregar equipa…</p>
      ) : (
        <table className={styles.table}>
          <thead>
            <tr>
              <th>Email</th>
              <th>Cargo</th>
              <th>Estado</th>
            </tr>
          </thead>
          <tbody>
            {members.map((m) => (
              <tr key={m.id}>
                <td>{m.email}</td>
                <td><span className={styles.roleTag}>{m.role}</span></td>
                <td>
                  {m.status === 'active' ? (
                    <span className={styles.statusActive}>● Ativo</span>
                  ) : (
                    <span className={styles.statusInvited}>○ Convidado</span>
                  )}
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      )}
    </div>
  )
}
