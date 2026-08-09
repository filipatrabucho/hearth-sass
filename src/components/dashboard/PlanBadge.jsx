import styles from './PlanBadge.module.css'

export function PlanBadge({ plan }) {
  if (!plan || plan === 'free') return null
  return <span className={styles.badge}>{plan === 'growth' ? 'Growth' : 'Pro'}</span>
}
