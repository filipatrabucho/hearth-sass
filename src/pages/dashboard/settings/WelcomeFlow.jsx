import { useEffect, useState } from 'react'
import { supabase } from '../../../lib/supabaseClient'
import { useAuth } from '../../../contexts/AuthContext'
import { UpgradeNotice } from '../../../components/dashboard/UpgradeNotice'
import { hasFeature, minPlanLabel } from '../../../lib/plans'
import styles from './Branding.module.css'

const DEFAULTS = {
  enabled: false,
  message_text: 'Bem-vindo(a) à comunidade! 🎉',
  embed_title: '',
  embed_description: '',
  embed_color: '#7B61FF',
  auto_role_id: '',
  dm_enabled: false,
  dm_message: '',
  verification_enabled: false,
  verification_type: 'button',
}

export function WelcomeFlow() {
  const { currentWorkspace } = useAuth()
  const [form, setForm] = useState(null)
  const [submitting, setSubmitting] = useState(false)
  const [feedback, setFeedback] = useState(null)

  useEffect(() => {
    if (!currentWorkspace) return
    supabase
      .from('welcome_flow_configs')
      .select('*')
      .eq('workspace_id', currentWorkspace.id)
      .maybeSingle()
      .then(({ data }) => setForm(data ? { ...DEFAULTS, ...data } : { ...DEFAULTS }))
  }, [currentWorkspace])

  function update(key, value) {
    setForm((f) => ({ ...f, [key]: value }))
  }

  async function handleSubmit(e) {
    e.preventDefault()
    if (!currentWorkspace) return
    setSubmitting(true)
    setFeedback(null)
    try {
      const { error } = await supabase
        .from('welcome_flow_configs')
        .upsert({ ...form, workspace_id: currentWorkspace.id, updated_at: new Date().toISOString() })
      if (error) throw error
      setFeedback({ type: 'ok', text: 'Configuração guardada.' })
    } catch (err) {
      setFeedback({ type: 'err', text: err.message || 'Não foi possível guardar.' })
    } finally {
      setSubmitting(false)
    }
  }

  if (currentWorkspace && !hasFeature(currentWorkspace.plan, 'welcome_flow')) {
    return (
      <UpgradeNotice
        icon="👋"
        title="Welcome flow"
        sub="Mensagem de boas-vindas personalizada, cargo automático e verificação antes de aceder ao servidor."
        requiredPlanLabel={minPlanLabel('welcome_flow')}
      />
    )
  }

  if (!form) return null

  return (
    <div>
      <h1 className="page-title">Welcome flow</h1>
      <p className="page-sub">
        Define a mensagem de boas-vindas, cargo automático e verificação. A entrega real depende do bot Hearth
        estar ligado a este servidor (Definições → Identidade visual → convite do bot).
      </p>

      <form className={styles.form} onSubmit={handleSubmit}>
        <label className={styles.colorField} style={{ flexDirection: 'row', alignItems: 'center', gap: 10, marginBottom: 24 }}>
          <input type="checkbox" checked={form.enabled} onChange={(e) => update('enabled', e.target.checked)} />
          <span className={styles.label}>Ativar welcome flow</span>
        </label>

        <div className={styles.field} style={{ marginBottom: 16 }}>
          <label className={styles.label}>Mensagem publicada no canal de boas-vindas</label>
          <input className={styles.input} value={form.message_text} onChange={(e) => update('message_text', e.target.value)} />
        </div>

        <div className={styles.field} style={{ marginBottom: 16 }}>
          <label className={styles.label}>Título do embed</label>
          <input className={styles.input} value={form.embed_title} onChange={(e) => update('embed_title', e.target.value)} />
        </div>
        <div className={styles.field} style={{ marginBottom: 16 }}>
          <label className={styles.label}>Descrição do embed</label>
          <input className={styles.input} value={form.embed_description} onChange={(e) => update('embed_description', e.target.value)} />
        </div>
        <div className={styles.field} style={{ marginBottom: 24 }}>
          <label className={styles.label}>Cor do embed</label>
          <input type="color" value={form.embed_color} onChange={(e) => update('embed_color', e.target.value)} />
        </div>

        <div className={styles.field} style={{ marginBottom: 24 }}>
          <label className={styles.label}>Cargo automático ao entrar (ID do cargo Discord)</label>
          <input className={styles.input} value={form.auto_role_id} onChange={(e) => update('auto_role_id', e.target.value)} />
        </div>

        <label className={styles.colorField} style={{ flexDirection: 'row', alignItems: 'center', gap: 10, marginBottom: 12 }}>
          <input type="checkbox" checked={form.dm_enabled} onChange={(e) => update('dm_enabled', e.target.checked)} />
          <span className={styles.label}>Enviar mensagem direta de onboarding</span>
        </label>
        <div className={styles.field} style={{ marginBottom: 24 }}>
          <label className={styles.label}>Texto da mensagem direta</label>
          <input className={styles.input} value={form.dm_message} onChange={(e) => update('dm_message', e.target.value)} />
        </div>

        <label className={styles.colorField} style={{ flexDirection: 'row', alignItems: 'center', gap: 10, marginBottom: 12 }}>
          <input
            type="checkbox"
            checked={form.verification_enabled}
            onChange={(e) => update('verification_enabled', e.target.checked)}
          />
          <span className={styles.label}>Exigir verificação antes de aceder ao servidor</span>
        </label>
        <div className={styles.field} style={{ marginBottom: 24 }}>
          <label className={styles.label}>Tipo de verificação</label>
          <select
            className={styles.input}
            value={form.verification_type}
            onChange={(e) => update('verification_type', e.target.value)}
          >
            <option value="button">Botão</option>
            <option value="reaction">Reação</option>
          </select>
        </div>

        <button type="submit" className="btn-primary" disabled={submitting}>
          {submitting ? 'A guardar…' : 'Guardar'}
        </button>

        {feedback && <p className={feedback.type === 'ok' ? styles.ok : styles.err}>{feedback.text}</p>}
      </form>
    </div>
  )
}
