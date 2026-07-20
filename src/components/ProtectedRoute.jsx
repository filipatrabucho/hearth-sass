import { Navigate, Outlet } from 'react-router-dom'
import { useAuth } from '../contexts/AuthContext'

export function ProtectedRoute() {
  const { user, authLoading, dataLoading, workspaces } = useAuth()

  if (authLoading || dataLoading) {
    return (
      <div style={{ minHeight: '100vh', display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
        <p style={{ color: 'var(--text-mid)' }}>A carregar…</p>
      </div>
    )
  }

  if (!user) return <Navigate to="/login" replace />
  if (workspaces.length === 0) return <Navigate to="/onboarding" replace />

  return <Outlet />
}
