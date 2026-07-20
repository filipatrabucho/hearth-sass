import { Routes, Route, Navigate } from 'react-router-dom'
import { ProtectedRoute } from './components/ProtectedRoute'
import { DashboardLayout } from './components/DashboardLayout'
import { PublicLayout } from './components/public/PublicLayout'
import { PublicWorkspaceProvider } from './contexts/PublicWorkspaceContext'
import { Login } from './pages/Login'
import { AuthCallback } from './pages/AuthCallback'
import { Onboarding } from './pages/Onboarding'

import { Home } from './pages/public/Home'
import { Rules } from './pages/public/Rules'
import { Team as PublicTeam } from './pages/public/Team'
import { Support } from './pages/public/Support'
import { Updates as PublicUpdates } from './pages/public/Updates'
import { Events as PublicEvents } from './pages/public/Events'

import { Analytics } from './pages/dashboard/Analytics'
import { Members } from './pages/dashboard/Members'
import { Moderation } from './pages/dashboard/Moderation'
import { Events as DashboardEvents } from './pages/dashboard/Events'
import { Tickets } from './pages/dashboard/Tickets'
import { XpLevels } from './pages/dashboard/XpLevels'
import { Posts } from './pages/dashboard/Posts'
import { Updates as DashboardUpdates } from './pages/dashboard/Updates'
import { Team } from './pages/dashboard/settings/Team'
import { Branding } from './pages/dashboard/settings/Branding'
import { WelcomeFlow } from './pages/dashboard/settings/WelcomeFlow'

function PublicSite() {
  return (
    <PublicWorkspaceProvider>
      <PublicLayout />
    </PublicWorkspaceProvider>
  )
}

export default function App() {
  return (
    <Routes>
      <Route element={<PublicSite />}>
        <Route path="/" element={<Home />} />
        <Route path="/regras" element={<Rules />} />
        <Route path="/equipa" element={<PublicTeam />} />
        <Route path="/eventos" element={<PublicEvents />} />
        <Route path="/updates" element={<PublicUpdates />} />
        <Route path="/suporte" element={<Support />} />
      </Route>

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
          <Route path="events" element={<DashboardEvents />} />
          <Route path="tickets" element={<Tickets />} />
          <Route path="xp" element={<XpLevels />} />
          <Route path="posts" element={<Posts />} />
          <Route path="updates" element={<DashboardUpdates />} />
          <Route path="settings/team" element={<Team />} />
          <Route path="settings/branding" element={<Branding />} />
          <Route path="settings/welcome" element={<WelcomeFlow />} />
        </Route>
      </Route>

      <Route path="*" element={<Navigate to="/" replace />} />
    </Routes>
  )
}
