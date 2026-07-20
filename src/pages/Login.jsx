import { useState } from 'react'
import { Navigate } from 'react-router-dom'
import { useAuth } from '../contexts/AuthContext'
import styles from './Login.module.css'

export function Login() {
  const { user, authLoading, signInWithDiscord } = useAuth()
  const [error, setError] = useState('')

  if (!authLoading && user) return <Navigate to="/dashboard" replace />

  async function handleDiscordLogin() {
    setError('')
    try {
      await signInWithDiscord()
    } catch (err) {
      setError('Não foi possível iniciar sessão. Tenta novamente.')
    }
  }

  return (
    <div className={styles.wrap}>
      <div className={styles.card}>
        <div className={styles.logo}>
          <div className={styles.logoMark} aria-hidden="true">
            <svg viewBox="0 0 16 16" fill="none" width="16" height="16">
              <path d="M8 2L13 6V14H10V10H6V14H3V6L8 2Z" fill="white" />
            </svg>
          </div>
          Hearth
        </div>

        <h1 className={styles.title}>Entrar no dashboard</h1>
        <p className={styles.sub}>
          Usa a tua conta Discord para geres a tua comunidade.
        </p>

        <button className={`btn-primary ${styles.discordBtn}`} onClick={handleDiscordLogin}>
          <span aria-hidden="true">🎮</span> Entrar com Discord
        </button>

        {error && <p className={styles.error}>{error}</p>}
      </div>
    </div>
  )
}
