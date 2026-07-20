import styles from './ComingSoon.module.css'

export function ComingSoon({ icon, title, sub, placeholder }) {
  return (
    <div>
      <div className={styles.badge}>
        <span aria-hidden="true">{icon}</span> Em desenvolvimento
      </div>
      <h1 className="page-title">{title}</h1>
      <p className="page-sub">{sub}</p>
      <div className={styles.placeholder}>{placeholder}</div>
    </div>
  )
}
