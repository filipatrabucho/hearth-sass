import { useEffect, useState, useCallback, Fragment } from 'react'
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

  const [editingId, setEditingId] = useState(null)
  const [editForm, setEditForm] = useState({ public_title: '', bio: '', show_on_team_page: false })
  const [savingProfile, setSavingProfile] = useState(false)

  const loadMembers = useCallback(async () => {
    if (!currentWorkspace) return
    setLoading(true)
    const { data } = await supabase
      .from('workspace_members')
      .select('id, email, role, status, invited_at, joined_at, public_title, bio, show_on_team_page')
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

  function startEdit(m) {
    setEditingId(m.id)
    setEditForm({
      public_title: m.public_title || '',
      bio: m.bio || '',
      show_on_team_page: m.show_on_team_page || false,
    })
  }

  async function saveProfile(id) {
    setSavingProfile(true)
    try {
      await supabase.from('workspace_members').update(editForm).eq('id', id)
      setEditingId(null)
      loadMembers()
    } finally {
      setSavingProfile(false)
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
              <th>Página /equipa</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            {members.map((m) => (
              <Fragment key={m.id}>
                <tr>
                  <td>{m.email}</td>
                  <td><span className={styles.roleTag}>{m.role}</span></td>
                  <td>
                    {m.status === 'active' ? (
                      <span className={styles.statusActive}>● Ativo</span>
                    ) : (
                      <span className={styles.statusInvited}>○ Convidado</span>
                    )}
                  </td>
                  <td>{m.show_on_team_page ? 'Visível' : 'Oculto'}</td>
                  <td>
                    {m.status === 'active' && (
                      <button
                        className="btn-secondary"
                        style={{ padding: '4px 10px', fontSize: 12 }}
                        onClick={() => (editingId === m.id ? setEditingId(null) : startEdit(m))}
                      >
                        {editingId === m.id ? 'Fechar' : 'Editar'}
                      </button>
                    )}
                  </td>
                </tr>
                {editingId === m.id && (
                  <tr>
                    <td colSpan={5}>
                      <div className={styles.editPanel}>
                        <div className={styles.field}>
                          <label className={styles.label}>Cargo público (ex: "Community Manager")</label>
                          <input
                            className={styles.input}
                            value={editForm.public_title}
                            onChange={(e) => setEditForm((f) => ({ ...f, public_title: e.target.value }))}
                          />
                        </div>
                        <div className={styles.field}>
                          <label className={styles.label}>Bio curta</label>
                          <input
                            className={styles.input}
                            value={editForm.bio}
                            onChange={(e) => setEditForm((f) => ({ ...f, bio: e.target.value }))}
                          />
                        </div>
                        <label className={styles.checkboxLabel}>
                          <input
                            type="checkbox"
                            checked={editForm.show_on_team_page}
                            onChange={(e) => setEditForm((f) => ({ ...f, show_on_team_page: e.target.checked }))}
                          />
                          Mostrar em /equipa
                        </label>
                        <button className="btn-primary" disabled={savingProfile} onClick={() => saveProfile(m.id)}>
                          {savingProfile ? 'A guardar…' : 'Guardar'}
                        </button>
                      </div>
                    </td>
                  </tr>
                )}
              </Fragment>
            ))}
          </tbody>
        </table>
      )}
    </div>
  )
}
