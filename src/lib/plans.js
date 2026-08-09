// Matriz de features por plano (ver roadmap Hearth). O valor é o plano
// mínimo que desbloqueia a feature — 'free' está sempre disponível.
const PLAN_RANK = { free: 0, pro: 1, growth: 2 }

export const FEATURE_MIN_PLAN = {
  // Free
  public_site: 'free',
  dashboard_basic: 'free',
  members_bans_logs: 'free',
  tickets: 'free',
  invite_progression: 'free',
  changelog: 'free',
  custom_theme: 'free',
  custom_logo_banner: 'free',

  // Pro
  custom_domain: 'pro',
  analytics_advanced: 'pro',
  activity_heatmap: 'pro',
  xp_levels: 'pro',
  xp_rewards: 'pro',
  welcome_flow: 'pro',
  automod: 'pro',
  antiraid: 'pro',
  event_rsvp: 'pro',
  event_reminders: 'pro',
  tickets_categories_faq: 'pro',
  polls_suggestions: 'pro',
  partners_affiliates: 'pro',

  // Growth
  churn_alerts: 'growth',
  paid_partner_spots: 'growth',
  affiliate_click_tracking: 'growth',
  staff_audit_log: 'growth',
  staff_performance: 'growth',
  tournaments: 'growth',
  nps: 'growth',
  embeddable_widget: 'growth',
  discord_subscriptions_sync: 'growth',
}

export function hasFeature(plan, feature) {
  const minPlan = FEATURE_MIN_PLAN[feature]
  if (!minPlan) return true
  const planRank = PLAN_RANK[plan] ?? 0
  return planRank >= PLAN_RANK[minPlan]
}

export function planLabel(plan) {
  return { free: 'Free', pro: 'Pro', growth: 'Growth' }[plan] || 'Free'
}

export function minPlanLabel(feature) {
  return planLabel(FEATURE_MIN_PLAN[feature])
}
