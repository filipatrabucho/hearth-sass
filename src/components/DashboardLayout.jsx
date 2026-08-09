import { useState } from 'react'
import { NavLink, Outlet, useNavigate } from 'react-router-dom'
import { useAuth } from '../contexts/AuthContext'
import { hasFeature, FEATURE_MIN_PLAN } from '../lib/plans'
import { PlanBadge } from './dashboard/PlanBadge'
import styles from './DashboardLayout.module.css'

const NAV_ITEMS = [
  { to: '/dashboard', end: true, icon: '📈', label: 'Analytics', feature: 'dashboard_basic' },
  { to: '/dashboard/members', icon: '👥', label: 'Membros', feature: 'members_bans_logs' },
  { to: '/dashboard/moderation', icon: '🛡️', label: 'Moderação', feature: 'automod' },
  { to: '/dashboard/events', icon: '🎟️', label: 'Eventos', feature: 'dashboard_basic' },
  { to: '/dashboard/tickets', icon: '💬', label: 'Tickets', feature: 'tickets' },
  { to: '/dashboard/xp', icon: '🎮', label: 'XP & Níveis', feature: 'xp_levels' },
  { to: '/dashboard/posts', icon: '📰', label: 'Publicações', feature: 'dashboard_basic' },
  { to: '/dashboard/updates', icon: '🗓️', label: 'Updates', feature: 'changelog' },
]

const SETTINGS_ITEMS = [
  { to: '/dashboard/settings/team', icon: '👤', label: 'Equipa', feature: 'dashboard_basic' },
  { to: '/dashboard/settings/branding', icon: '🎨', label: 'Identidade visual', feature: 'custom_theme' },
  { to: '/dashboard/settings/welcome', icon: '👋', label: 'Welcome flow', feature: 'welcome_flow' },
]

export function DashboardLayout() {
  const { profile, workspaces, currentWorkspace, setCurrentWorkspaceId, signOut } = useAuth()
  const navigate = useNavigate()
  const [wsOpen, setWsOpen] = useState(false)
  const [mobileOpen, setMobileOpen] = useState(false)

  async function handleSignOut() {
    await signOut()
    navigate('/login', { replace: true })
  }

  const initials = (profile?.discord_username || profile?.email || '?').slice(0, 2).toUpperCase()

  return (
    <div className={`${styles.shell} ${mobileOpen ? styles.sidebarOpen : ''}`}>
      {mobileOpen && (
        <div className={styles.overlay} onClick={() => setMobileOpen(false)} />
      )}

      <aside className={styles.sidebar}>
        <div className={styles.logo}>
          <div className={styles.logoMark} aria-hidden="true">
            <svg viewBox="0 0 16 16" fill="none" width="14" height="14">
              <path d="M8 2L13 6V14H10V10H6V14H3V6L8 2Z" fill="white" />
            </svg>
          </div>
          Hearth
        </div>

        <nav className={styles.nav}>
          {NAV_ITEMS.map((item) => (
            <NavLink
              key={item.to}
              to={item.to}
              end={item.end}
              onClick={() => setMobileOpen(false)}
              className={({ isActive }) => `${styles.navLink} ${isActive ? styles.active : ''}`}
            >
              <span className={styles.navIcon} aria-hidden="true">{item.icon}</span>
              {item.label}
              {!hasFeature(currentWorkspace?.plan, item.feature) && (
                <PlanBadge plan={FEATURE_MIN_PLAN[item.feature]} />
              )}
            </NavLink>
          ))}

          <div className={styles.navDivider} />

          {SETTINGS_ITEMS.map((item) => (
            <NavLink
              key={item.to}
              to={item.to}
              onClick={() => setMobileOpen(false)}
              className={({ isActive }) => `${styles.navLink} ${isActive ? styles.active : ''}`}
            >
              <span className={styles.navIcon} aria-hidden="true">{item.icon}</span>
              {item.label}
              {!hasFeature(currentWorkspace?.plan, item.feature) && (
                <PlanBadge plan={FEATURE_MIN_PLAN[item.feature]} />
              )}
            </NavLink>
          ))}
        </nav>
      </aside>

      <div className={styles.main}>
        <header className={styles.topbar}>
          <button className={styles.hamburger} aria-label="Menu" onClick={() => setMobileOpen((v) => !v)}>
            <span /><span /><span />
          </button>

          <div className={styles.wsSwitcher}>
            <button className={styles.wsButton} onClick={() => setWsOpen((v) => !v)}>
              {currentWorkspace?.name || 'Workspace'} <span aria-hidden="true">▾</span>
            </button>
            {wsOpen && (
              <div className={styles.wsDropdown}>
                {workspaces.map((w) => (
                  <button
                    key={w.id}
                    className={`${styles.wsOption} ${w.id === currentWorkspace?.id ? styles.active : ''}`}
                    onClick={() => { setCurrentWorkspaceId(w.id); setWsOpen(false) }}
                  >
                    {w.name}
                  </button>
                ))}
              </div>
            )}
          </div>

          <div className={styles.userMenu}>
            <button className={styles.signOut} onClick={handleSignOut}>Sair</button>
            <div className={styles.avatar} title={profile?.discord_username}>
              {profile?.discord_avatar ? (
                <img
                  src={profile.discord_avatar}
                  alt=""
                  style={{ width: '100%', height: '100%', borderRadius: '50%' }}
                />
              ) : (
                initials
              )}
            </div>
          </div>
        </header>

        <main className={styles.content}>
          <Outlet />
        </main>
      </div>
    </div>
  )
}