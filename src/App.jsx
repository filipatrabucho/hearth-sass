import { Routes, Route, Navigate } from 'react-router-dom'
import { ProtectedRoute } from './components/ProtectedRoute'
import { DashboardLayout } from './components/DashboardLayout'
import { Login } from './pages/Login'
import { AuthCallback } from './pages/AuthCallback'
import { Onboarding } from './pages/Onboarding'
import { Analytics } from './pages/dashboard/Analytics'
import { Members } from './pages/dashboard/Members'
import { Moderation } from './pages/dashboard/Moderation'
import { Events } from './pages/dashboard/Events'
import { Tickets } from './pages/dashboard/Tickets'
import { XpLevels } from './pages/dashboard/XpLevels'
import { Team } from './pages/dashboard/settings/Team'

export default function App() {
  return (
    <Routes>
      <Route path="/" element={<Navigate to="/login" replace />} />
      <Route path="/login" element={<Login />} />
      <Route path="/auth/callback" element={<AuthCallback />} />

      {/* Onboarding também exige sessão, mas fica fora do ProtectedRoute
          porque é precisamente a rota para quem ainda não tem workspace */}
      <Route path="/onboarding" element={<Onboarding />} />

      <Route element={<ProtectedRoute />}>
        <Route path="/dashboard" element={<DashboardLayout />}>
          <Route index element={<Analytics />} />
          <Route path="members" element={<Members />} />
          <Route path="moderation" element={<Moderation />} />
          <Route path="events" element={<Events />} />
          <Route path="tickets" element={<Tickets />} />
          <Route path="xp" element={<XpLevels />} />
          <Route path="settings/team" element={<Team />} />
        </Route>
      </Route>

      <Route path="*" element={<Navigate to="/login" replace />} />
    </Routes>
  )
}
