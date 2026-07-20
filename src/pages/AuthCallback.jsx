import { useEffect } from 'react'
import { useNavigate } from 'react-router-dom'
import { useAuth } from '../contexts/AuthContext'

export function AuthCallback() {
  const { user, authLoading, dataLoading, workspaces } = useAuth()
  const navigate = useNavigate()

  useEffect(() => {
    // Espera o Supabase resolver a sessão a partir do URL (#access_token=...)
    // e o AuthContext buscar profile + workspaces antes de decidir para onde ir.
    if (authLoading || dataLoading) return

    if (!user) {
      navigate('/login', { replace: true })
      return
    }

    if (workspaces.length === 0) {
      navigate('/onboarding', { replace: true })
    } else {
      navigate('/dashboard', { replace: true })
    }
  }, [authLoading, dataLoading, user, workspaces, navigate])

  return (
    <div style={{ minHeight: '100vh', display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
      <p style={{ color: 'var(--text-mid)' }}>A entrar…</p>
    </div>
  )
}
