import { useEffect, useState } from 'react'
import { supabase } from '../../lib/supabaseClient'
import { useAuth } from '../../contexts/AuthContext'
import { LineChart, BarChart } from '../../components/dashboard/charts'
import styles from './Analytics.module.css'

const ACTION_LABELS = { warn: 'Avisos', kick: 'Kicks', ban: 'Bans', timeout: 'Timeouts' }

function lastNDays(n) {
  return Array.from({ length: n }, (_, i) => {
    const d = new Date()
    d.setHours(0, 0, 0, 0)
    d.setDate(d.getDate() - (n - 1 - i))
    return d
  })
}

export function Analytics() {
  const { currentWorkspace } = useAuth()
  const [members, setMembers] = useState([])
  const [logs, setLogs] = useState([])
  const [openTickets, setOpenTickets] = useState(0)
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    if (!currentWorkspace) return
    let cancelled = false

    async function load() {
      const since30 = new Date(Date.now() - 30 * 24 * 60 * 60 * 1000).toISOString()
      const [{ data: memberRows }, { data: logRows }, { count }] = await Promise.all([
        supabase
          .from('community_members')
          .select('id, joined_discord_at, status')
          .eq('workspace_id', currentWorkspace.id),
        supabase
          .from('moderation_logs')
          .select('action, created_at')
          .eq('workspace_id', currentWorkspace.id)
          .gte('created_at', since30),
        supabase
          .from('support_requests')
          .select('id', { count: 'exact', head: true })
          .eq('workspace_id', currentWorkspace.id)
          .eq('status', 'open'),
      ])
      if (cancelled) return
      setMembers(memberRows || [])
      setLogs(logRows || [])
      setOpenTickets(count || 0)
      setLoading(false)
    }

    load()
    return () => { cancelled = true }
  }, [currentWorkspace])

  if (loading) return <p style={{ color: 'var(--text-muted)' }}>A carregar…</p>

  const days = lastNDays(14)
  const growthData = days.map((day) => {
    const next = new Date(day)
    next.setDate(next.getDate() + 1)
    const count = members.filter((m) => {
      const joined = new Date(m.joined_discord_at)
      return joined >= day && joined < next
    }).length
    return { label: day.toLocaleDateString('pt-PT', { day: '2-digit', month: '2-digit' }), value: count }
  })

  const actionCounts = ['warn', 'kick', 'ban', 'timeout'].map((action) => ({
    label: ACTION_LABELS[action],
    value: logs.filter((l) => l.action === action).length,
  }))

  const newLast7Days = members.filter(
    (m) => new Date(m.joined_discord_at) >= new Date(Date.now() - 7 * 24 * 60 * 60 * 1000)
  ).length

  return (
    <div>
      <h1 className="page-title">Analytics</h1>
      <p className="page-sub">Visão geral básica da comunidade. Retenção avançada e heatmap de atividade são Pro.</p>

      <div className={styles.statRow}>
        <div className={styles.stat}>
          <div className={styles.statValue}>{members.length}</div>
          <div className={styles.statLabel}>Membros no roster</div>
        </div>
        <div className={styles.stat}>
          <div className={styles.statValue}>{newLast7Days}</div>
          <div className={styles.statLabel}>Novos nos últimos 7 dias</div>
        </div>
        <div className={styles.stat}>
          <div className={styles.statValue}>{openTickets}</div>
          <div className={styles.statLabel}>Tickets abertos</div>
        </div>
      </div>

      <div className={styles.panels}>
        <div className={styles.panel}>
          <div className={styles.panelTitle}>Novos membros (últimos 14 dias)</div>
          <LineChart data={growthData} />
        </div>
        <div className={styles.panel}>
          <div className={styles.panelTitle}>Ações de moderação (últimos 30 dias)</div>
          <BarChart data={actionCounts} />
        </div>
      </div>

      <div className={styles.upgradeHint}>
        🔒 Retenção a 7/30 dias, mapa de calor de atividade e crescimento orgânico vs convites
        estão disponíveis no plano Pro.
      </div>
    </div>
  )
}
