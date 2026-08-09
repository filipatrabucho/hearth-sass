import { useEffect, useState, useCallback, Fragment } from 'react'
import { supabase } from '../../lib/supabaseClient'
import { useAuth } from '../../contexts/AuthContext'
import styles from './AdminCrud.module.css'

const STATUS_LABELS = { open: 'Aberto', in_progress: 'Em curso', closed: 'Fechado' }
const STATUS_FILTERS = ['all', 'open', 'in_progress', 'closed']

export function Tickets() {
  const { currentWorkspace, user, replyToTicket } = useAuth()
  const [tickets, setTickets] = useState([])
  const [loading, setLoading] = useState(true)
  const [filter, setFilter] = useState('all')

  const [openId, setOpenId] = useState(null)
  const [replies, setReplies] = useState([])
  const [replyBody, setReplyBody] = useState('')
  const [submitting, setSubmitting] = useState(false)

  const load = useCallback(async () => {
    if (!currentWorkspace) return
    setLoading(true)
    const { data } = await supabase
      .from('support_requests')
      .select('*')
      .eq('workspace_id', currentWorkspace.id)
      .order('created_at', { ascending: false })
    setTickets(data || [])
    setLoading(false)
  }, [currentWorkspace])

  useEffect(() => { load() }, [load])

  async function openTicket(ticket) {
    if (openId === ticket.id) {
      setOpenId(null)
      return
    }
    setOpenId(ticket.id)
    setReplyBody('')
    const { data } = await supabase
      .from('ticket_replies')
      .select('*')
      .eq('support_request_id', ticket.id)
      .order('created_at', { ascending: true })
    setReplies(data || [])
  }

  async function updateStatus(ticket, status) {
    await supabase.from('support_requests').update({ status }).eq('id', ticket.id)
    load()
  }

  async function assignToMe(ticket) {
    await supabase.from('support_requests').update({ assigned_to: user.id }).eq('id', ticket.id)
    load()
  }

  async function handleReply(ticket) {
    if (!replyBody.trim()) return
    setSubmitting(true)
    try {
      await replyToTicket({ ticketId: ticket.id, body: replyBody.trim() })
      setReplyBody('')
      const { data } = await supabase
        .from('ticket_replies')
        .select('*')
        .eq('support_request_id', ticket.id)
        .order('created_at', { ascending: true })
      setReplies(data || [])
      if (ticket.status === 'open') await updateStatus(ticket, 'in_progress')
    } finally {
      setSubmitting(false)
    }
  }

  const visibleTickets = filter === 'all' ? tickets : tickets.filter((t) => t.status === filter)

  return (
    <div>
      <h1 className="page-title">Tickets & Suporte</h1>
      <p className="page-sub">Pedidos submetidos em /suporte. Responde, atribui a ti e muda o estado.</p>

      <div className={styles.tabs}>
        {STATUS_FILTERS.map((s) => (
          <button
            key={s}
            className={`${styles.tab} ${filter === s ? styles.tabActive : ''}`}
            onClick={() => setFilter(s)}
          >
            {s === 'all' ? `Todos (${tickets.length})` : `${STATUS_LABELS[s]} (${tickets.filter((t) => t.status === s).length})`}
          </button>
        ))}
      </div>

      {loading ? (
        <p style={{ color: 'var(--text-muted)', marginTop: 20 }}>A carregar…</p>
      ) : (
        <table className={styles.table}>
          <thead>
            <tr><th>De</th><th>Assunto</th><th>Estado</th><th>Atribuído</th><th></th></tr>
          </thead>
          <tbody>
            {visibleTickets.map((t) => (
              <Fragment key={t.id}>
                <tr>
                  <td>{t.name} <span style={{ color: 'var(--text-muted)' }}>({t.email})</span></td>
                  <td>{t.subject || '—'}</td>
                  <td>
                    <select
                      className={styles.select}
                      value={t.status}
                      onChange={(e) => updateStatus(t, e.target.value)}
                    >
                      <option value="open">Aberto</option>
                      <option value="in_progress">Em curso</option>
                      <option value="closed">Fechado</option>
                    </select>
                  </td>
                  <td>{t.assigned_to === user.id ? 'Ti' : t.assigned_to ? 'Staff' : '—'}</td>
                  <td>
                    <div className={styles.inlineActions}>
                      {t.assigned_to !== user.id && (
                        <button className={styles.smallBtn} onClick={() => assignToMe(t)}>Atribuir a mim</button>
                      )}
                      <button className={styles.smallBtn} onClick={() => openTicket(t)}>
                        {openId === t.id ? 'Fechar' : 'Ver / Responder'}
                      </button>
                    </div>
                  </td>
                </tr>
                {openId === t.id && (
                  <tr>
                    <td colSpan={5}>
                      <div style={{ padding: '12px 0' }}>
                        <p style={{ fontSize: 14, color: 'var(--text-mid)', marginBottom: 12, whiteSpace: 'pre-wrap' }}>
                          {t.message}
                        </p>
                        {replies.map((r) => (
                          <div key={r.id} style={{ borderTop: '1px solid var(--border)', padding: '10px 0', fontSize: 13 }}>
                            <span style={{ color: 'var(--text-muted)' }}>
                              {new Date(r.created_at).toLocaleString('pt-PT')}
                            </span>
                            <p style={{ whiteSpace: 'pre-wrap', marginTop: 4 }}>{r.body}</p>
                          </div>
                        ))}
                        <textarea
                          className={styles.textarea}
                          rows={3}
                          placeholder="Escreve uma resposta…"
                          value={replyBody}
                          onChange={(e) => setReplyBody(e.target.value)}
                          style={{ marginTop: 12 }}
                        />
                        <button
                          className="btn-primary"
                          style={{ marginTop: 8 }}
                          disabled={submitting}
                          onClick={() => handleReply(t)}
                        >
                          {submitting ? 'A enviar…' : 'Responder'}
                        </button>
                      </div>
                    </td>
                  </tr>
                )}
              </Fragment>
            ))}
            {visibleTickets.length === 0 && (
              <tr><td colSpan={5} style={{ color: 'var(--text-muted)' }}>Sem tickets aqui.</td></tr>
            )}
          </tbody>
        </table>
      )}
    </div>
  )
}
