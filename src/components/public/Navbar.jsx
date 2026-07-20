import { useState } from 'react'
import { NavLink, Link } from 'react-router-dom'
import { useAuth } from '../../contexts/AuthContext'
import { usePublicWorkspace } from '../../contexts/PublicWorkspaceContext'
import styles from './Navbar.module.css'

const LINKS = [
  { to: '/', label: 'Início', end: true },
  { to: '/eventos', label: 'Eventos' },
  { to: '/regras', label: 'Regras' },
  { to: '/equipa', label: 'Equipa' },
  { to: '/updates', label: 'Updates' },
  { to: '/suporte', label: 'Suporte' },
]

export function Navbar() {
  const { user } = useAuth()
  const { workspace } = usePublicWorkspace()
  const [open, setOpen] = useState(false)

  return (
    <header className={styles.header}>
      <div className={styles.inner}>
        <Link to="/" className={styles.logo} onClick={() => setOpen(false)}>
          {workspace?.logo_url ? (
            <img src={workspace.logo_url} alt="" className={styles.logoImg} />
          ) : (
            <div className={styles.logoMark} aria-hidden="true">
              <svg viewBox="0 0 16 16" fill="none" width="14" height="14">
                <path d="M8 2L13 6V14H10V10H6V14H3V6L8 2Z" fill="white" />
              </svg>
            </div>
          )}
          {workspace?.name || 'Hearth'}
        </Link>

        <nav className={`${styles.nav} ${open ? styles.navOpen : ''}`}>
          {LINKS.map((link) => (
            <NavLink
              key={link.to}
              to={link.to}
              end={link.end}
              onClick={() => setOpen(false)}
              className={({ isActive }) => `${styles.link} ${isActive ? styles.active : ''}`}
            >
              {link.label}
            </NavLink>
          ))}
        </nav>

        <div className={styles.actions}>
          {user ? (
            <Link to="/dashboard" className="btn-primary">Dashboard</Link>
          ) : (
            <Link to="/login" className="btn-primary">Entrar</Link>
          )}
        </div>

        <button
          className={styles.hamburger}
          aria-label="Menu"
          aria-expanded={open}
          onClick={() => setOpen((v) => !v)}
        >
          <span /><span /><span />
        </button>
      </div>
    </header>
  )
}
