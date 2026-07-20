import { useState } from 'react'
import { Navigate, useNavigate } from 'react-router-dom'
import { useAuth } from '../contexts/AuthContext'
import styles from './Onboarding.module.css'

export function Onboarding() {
  const { user, authLoading, dataLoading, workspaces, createWorkspace } = useAuth()
  const navigate = useNavigate()
  const [name, setName] = useState('')
  const [discordGuildId, setDiscordGuildId] = useState('')
  const [error, setError] = useState('')
  const [submitting, setSubmitting] = useState(false)

  if (authLoading || dataLoading) return null
  if (!user) return <Navigate to="/login" replace />
  if (workspaces.length > 0) return <Navigate to="/dashboard" replace />

  async function handleSubmit(e) {
    e.preventDefault()
    setError('')
    setSubmitting(true)
    try {
      await createWorkspace({ name, discordGuildId })
      navigate('/dashboard', { replace: true })
    } catch (err) {
      setError(err.message || 'Não foi possível criar o workspace.')
    } finally {
      setSubmitting(false)
    }
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
