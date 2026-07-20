import { useState } from 'react'
import { Navigate, useNavigate } from 'react-router-dom'
import { useAuth } from '../contexts/AuthContext'
import styles from './Onboarding.module.css'

const DISCORD_CLIENT_ID = import.meta.env.VITE_DISCORD_CLIENT_ID
const BOT_PERMISSIONS = '268435459' // ver canais, gerir cargos, enviar mensagens, moderar membros

export function Onboarding() {
  const { user, authLoading, dataLoading, workspaces, createWorkspace, syncWorkspace } = useAuth()
  const navigate = useNavigate()
  const [step, setStep] = useState('form') // 'form' | 'invite-bot'
  const [createdWorkspace, setCreatedWorkspace] = useState(null)

  const [name, setName] = useState('')
  const [discordGuildId, setDiscordGuildId] = useState('')
  const [error, setError] = useState('')
  const [submitting, setSubmitting] = useState(false)
  const [syncing, setSyncing] = useState(false)
  const [syncFeedback, setSyncFeedback] = useState('')

  if (authLoading || dataLoading) return null
  if (!user) return <Navigate to="/login" replace />
  if (workspaces.length > 0 && step === 'form') return <Navigate to="/dashboard" replace />

  async function handleSubmit(e) {
    e.preventDefault()
    setError('')
    setSubmitting(true)
    try {
      const workspace = await createWorkspace({ name, discordGuildId })
      setCreatedWorkspace(workspace)
      setStep('invite-bot')
    } catch (err) {
      setError(err.message || 'Não foi possível criar o workspace.')
    } finally {
      setSubmitting(false)
    }
  }

  async function handleSync() {
    if (!createdWorkspace) return
    setSyncing(true)
    setSyncFeedback('')
    try {
      await syncWorkspace({ workspaceId: createdWorkspace.id })
      setSyncFeedback('Sincronização pedida. Isto vai ficar completo assim que o bot Hearth estiver ligado ao servidor.')
    } catch (err) {
      setSyncFeedback(err.message || 'Não foi possível sincronizar.')
    } finally {
      setSyncing(false)
    }
  }

  const inviteUrl = DISCORD_CLIENT_ID
    ? `https://discord.com/oauth2/authorize?client_id=${DISCORD_CLIENT_ID}&permissions=${BOT_PERMISSIONS}&scope=bot%20applications.commands`
    : null

  if (step === 'invite-bot') {
    return (
      <div className={styles.wrap}>
        <div className={styles.card}>
          <h1 className={styles.title}>Convida o bot Hearth</h1>
          <p className={styles.sub}>
            O workspace "{createdWorkspace?.name}" foi criado. Para ativar analytics, moderação e o welcome flow,
            convida o bot para o teu servidor Discord e depois sincroniza.
          </p>

          {inviteUrl ? (
            <a href={inviteUrl} target="_blank" rel="noreferrer" className={`btn-primary ${styles.submit}`}>
              Convidar bot para o Discord
            </a>
          ) : (
            <p className={styles.hint}>
              VITE_DISCORD_CLIENT_ID não está configurado — define esta variável de ambiente para gerar o link de convite do bot.
            </p>
          )}

          <button
            type="button"
            className={`btn-secondary ${styles.submit}`}
            style={{ marginTop: 12 }}
            onClick={handleSync}
            disabled={syncing}
          >
            {syncing ? 'A sincronizar…' : 'Já convidei — sincronizar'}
          </button>

          {syncFeedback && <p className={styles.hint}>{syncFeedback}</p>}

          <button
            type="button"
            className={`btn-primary ${styles.submit}`}
            style={{ marginTop: 20 }}
            onClick={() => navigate('/dashboard', { replace: true })}
          >
            Continuar para o dashboard
          </button>
        </div>
      </div>
    )
  }

  return (
    <div className={styles.wrap}>
      <div className={styles.card}>
        <h1 className={styles.title}>Cria o teu workspace</h1>
        <p className={styles.sub}>
          Um workspace representa o teu servidor Discord. Podes ligar o bot mais tarde —
          por agora só precisamos do nome.
        </p>

        <form onSubmit={handleSubmit}>
          <div className={styles.field}>
            <label className={styles.label} htmlFor="ws-name">Nome do servidor</label>
            <input
              id="ws-name"
              className={styles.input}
              placeholder="Ex: Club Party"
              required
              value={name}
              onChange={(e) => setName(e.target.value)}
            />
          </div>

          <div className={styles.field}>
            <label className={styles.label} htmlFor="ws-guild">ID do servidor Discord (opcional)</label>
            <input
              id="ws-guild"
              className={styles.input}
              placeholder="123456789012345678"
              value={discordGuildId}
              onChange={(e) => setDiscordGuildId(e.target.value)}
            />
            <p className={styles.hint}>
              Podes adicionar isto depois de convidares o bot Hearth para o servidor.
            </p>
          </div>

          <button type="submit" className={`btn-primary ${styles.submit}`} disabled={submitting}>
            {submitting ? 'A criar…' : 'Criar workspace'}
          </button>

          {error && <p className={styles.error}>{error}</p>}
        </form>
      </div>
    </div>
  )
}
