import { useEffect, useState, useCallback, Fragment } from 'react'
import { supabase } from '../../lib/supabaseClient'
import { useAuth } from '../../contexts/AuthContext'
import styles from './AdminCrud.module.css'

const ACTION_LABELS = { warn: 'Aviso', kick: 'Kick', ban: 'Ban', unban: 'Unban', timeout: 'Timeout', note: 'Nota' }
const STATUS_LABELS = { active: 'Ativo', kicked: 'Expulso', banned: 'Banido', left: 'Saiu' }

export function Members() {
  const { currentWorkspace, user } = useAuth()
  const [tab, setTab] = useState('members')

  const [members, setMembers] = useState([])
  const [bans, setBans] = useState([])
  const [logs, setLogs] = useState([])
  const [loading, setLoading] = useState(true)

  const [addForm, setAddForm] = useState({ username: '', discord_user_id: '' })
  const [adding, setAdding] = useState(false)

  const [actionMemberId, setActionMemberId] = useState(null)
  const [reason, setReason] = useState('')
  const [actionSubmitting, setActionSubmitting] = useState(false)

  const load = useCallback(async () => {
    if (!currentWorkspace) return
    setLoading(true)
    const [{ data: memberRows }, { data: banRows }, { data: logRows }] = await Promise.all([
      supabase
        .from('community_members')
        .select('*')
        .eq('workspace_id', currentWorkspace.id)
        .order('created_at', { ascending: false }),
      supabase
        .from('member_bans')
        .select('*')
        .eq('workspace_id', currentWorkspace.id)
        .order('banned_at', { ascending: false }),
      supabase
        .from('moderation_logs')
        .select('*')
        .eq('workspace_id', currentWorkspace.id)
        .order('created_at', { ascending: false })
        .limit(100),
    ])
    setMembers(memberRows || [])
    setBans(banRows || [])
    setLogs(logRows || [])
    setLoading(false)
  }, [currentWorkspace])

  useEffect(() => { load() }, [load])

  async function handleAdd(e) {
    e.preventDefault()
    if (!currentWorkspace) return
    setAdding(true)
    try {
      const { error } = await supabase.from('community_members').insert({
        workspace_id: currentWorkspace.id,
        username: addForm.username.trim(),
        discord_user_id: addForm.discord_user_id.trim() || null,
      })
      if (error) throw error
      setAddForm({ username: '', discord_user_id: '' })
      load()
    } finally {
      setAdding(false)
    }
  }

  async function logAction(member, action) {
    setActionSubmitting(true)
    try {
      await supabase.from('moderation_logs').insert({
        workspace_id: currentWorkspace.id,
        member_id: member.id,
        member_label: member.username,
        action,
        reason: reason.trim() || null,
        created_by: user.id,
      })

      if (action === 'kick') {
        await supabase.from('community_members').update({ status: 'kicked' }).eq('id', member.id)
      }
      if (action === 'ban') {
        await supabase.from('community_members').update({ status: 'banned' }).eq('id', member.id)
        await supabase.from('member_bans').insert({
          workspace_id: currentWorkspace.id,
          member_id: member.id,
          member_label: member.username,
          reason: reason.trim() || null,
          banned_by: user.id,
        })
      }

      setActionMemberId(null)
      setReason('')
      load()
    } finally {
      setActionSubmitting(false)
    }
  }

  async function liftBan(ban) {
    await supabase
      .from('member_bans')
      .update({ active: false, unbanned_at: new Date().toISOString(), unbanned_by: user.id })
      .eq('id', ban.id)
    if (ban.member_id) {
      await supabase.from('community_members').update({ status: 'active' }).eq('id', ban.member_id)
    }
    await supabase.from('moderation_logs').insert({
      workspace_id: currentWorkspace.id,
      member_id: ban.member_id,
      member_label: ban.member_label,
      action: 'unban',
      created_by: user.id,
    })
    load()
  }

  return (
    <div>
      <h1 className="page-title">Membros</h1>
      <p className="page-sub">
        Roster da comunidade, bans e histórico de moderação. Sem o bot Discord ligado, adiciona membros
        manualmente aqui — quando o bot existir, esta lista passa a sincronizar sozinha.
      </p>

      <div className={styles.tabs}>
        {[
          ['members', `Membros (${members.length})`],
          ['bans', `Bans (${bans.filter((b) => b.active).length})`],
          ['logs', `Logs (${logs.length})`],
        ].map(([key, label]) => (
          <button
            key={key}
            className={`${styles.tab} ${tab === key ? styles.tabActive : ''}`}
            onClick={() => setTab(key)}
          >
            {label}
          </button>
        ))}
      </div>

      {loading ? (
        <p style={{ color: 'var(--text-muted)', marginTop: 20 }}>A carregar…</p>
      ) : tab === 'members' ? (
        <>
          <form className={styles.form} onSubmit={handleAdd}>
            <div className={styles.field}>
              <label className={styles.label} htmlFor="username">Nome de utilizador Discord</label>
              <input
                id="username"
                className={styles.input}
                required
                value={addForm.username}
                onChange={(e) => setAddForm((f) => ({ ...f, username: e.target.value }))}
              />
            </div>
            <div className={styles.field}>
              <label className={styles.label} htmlFor="discord_user_id">ID do Discord (opcional)</label>
              <input
                id="discord_user_id"
                className={styles.input}
                value={addForm.discord_user_id}
                onChange={(e) => setAddForm((f) => ({ ...f, discord_user_id: e.target.value }))}
              />
            </div>
            <div className={styles.formActions}>
              <button type="submit" className="btn-primary" disabled={adding}>
                {adding ? 'A adicionar…' : 'Adicionar membro'}
              </button>
            </div>
          </form>

          <table className={styles.table}>
            <thead>
              <tr><th>Utilizador</th><th>Estado</th><th>Desde</th><th></th></tr>
            </thead>
            <tbody>
              {members.map((m) => (
                <Fragment key={m.id}>
                  <tr>
                    <td>{m.username}</td>
                    <td><span className={styles.pill}>{STATUS_LABELS[m.status] || m.status}</span></td>
                    <td>{new Date(m.joined_discord_at).toLocaleDateString('pt-PT')}</td>
                    <td>
                      <button
                        className={styles.smallBtn}
                        onClick={() => {
                          setActionMemberId(actionMemberId === m.id ? null : m.id)
                          setReason('')
                        }}
                      >
                        {actionMemberId === m.id ? 'Fechar' : 'Moderar'}
                      </button>
                    </td>
                  </tr>
                  {actionMemberId === m.id && (
                    <tr key={`${m.id}-actions`}>
                      <td colSpan={4}>
                        <div className={styles.inlineActions} style={{ padding: '10px 0', alignItems: 'center' }}>
                          <input
                            className={styles.input}
                            style={{ maxWidth: 280 }}
                            placeholder="Motivo (opcional)"
                            value={reason}
                            onChange={(e) => setReason(e.target.value)}
                          />
                          <button className={styles.smallBtn} disabled={actionSubmitting} onClick={() => logAction(m, 'warn')}>Avisar</button>
                          <button className={styles.smallBtn} disabled={actionSubmitting} onClick={() => logAction(m, 'timeout')}>Timeout</button>
                          <button className={styles.smallBtn} disabled={actionSubmitting} onClick={() => logAction(m, 'kick')}>Kick</button>
                          <button className={`${styles.smallBtn} ${styles.smallBtnDanger}`} disabled={actionSubmitting} onClick={() => logAction(m, 'ban')}>Ban</button>
                        </div>
                      </td>
                    </tr>
                  )}
                </Fragment>
              ))}
              {members.length === 0 && (
                <tr><td colSpan={4} style={{ color: 'var(--text-muted)' }}>Ainda não há membros registados.</td></tr>
              )}
            </tbody>
          </table>
        </>
      ) : tab === 'bans' ? (
        <table className={styles.table}>
          <thead>
            <tr><th>Membro</th><th>Motivo</th><th>Data</th><th>Estado</th><th></th></tr>
          </thead>
          <tbody>
            {bans.map((b) => (
              <tr key={b.id}>
                <td>{b.member_label}</td>
                <td>{b.reason || '—'}</td>
                <td>{new Date(b.banned_at).toLocaleDateString('pt-PT')}</td>
                <td>{b.active ? <span style={{ color: 'var(--red)' }}>● Ativo</span> : <span style={{ color: 'var(--text-muted)' }}>○ Levantado</span>}</td>
                <td>
                  {b.active && (
                    <button className={styles.smallBtn} onClick={() => liftBan(b)}>Levantar ban</button>
                  )}
                </td>
              </tr>
            ))}
            {bans.length === 0 && (
              <tr><td colSpan={5} style={{ color: 'var(--text-muted)' }}>Sem bans registados.</td></tr>
            )}
          </tbody>
        </table>
      ) : (
        <table className={styles.table}>
          <thead>
            <tr><th>Ação</th><th>Membro</th><th>Motivo</th><th>Data</th></tr>
          </thead>
          <tbody>
            {logs.map((l) => (
              <tr key={l.id}>
                <td><span className={styles.pill}>{ACTION_LABELS[l.action] || l.action}</span></td>
                <td>{l.member_label}</td>
                <td>{l.reason || '—'}</td>
                <td>{new Date(l.created_at).toLocaleString('pt-PT')}</td>
              </tr>
            ))}
            {logs.length === 0 && (
              <tr><td colSpan={4} style={{ color: 'var(--text-muted)' }}>Sem atividade registada.</td></tr>
            )}
          </tbody>
        </table>
      )}
    </div>
  )
}
