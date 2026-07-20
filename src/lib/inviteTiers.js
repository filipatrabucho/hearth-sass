// Progressão de convites: Bronze → Emerald.
// Os limiares são os mesmos para todos os workspaces por agora — no futuro
// podem passar a ser configuráveis no dashboard, tal como as recompensas de XP.
export const INVITE_TIERS = [
  { key: 'bronze', name: 'Bronze', min: 0, color: '#CD7F32' },
  { key: 'prata', name: 'Prata', min: 5, color: '#C0C0C0' },
  { key: 'ouro', name: 'Ouro', min: 15, color: '#E8B923' },
  { key: 'platina', name: 'Platina', min: 30, color: '#9FD8E8' },
  { key: 'emerald', name: 'Emerald', min: 50, color: '#00D4A0' },
]

export function getTierForInvites(count) {
  let current = INVITE_TIERS[0]
  for (const tier of INVITE_TIERS) {
    if (count >= tier.min) current = tier
  }
  return current
}

export function getNextTier(count) {
  return INVITE_TIERS.find((t) => t.min > count) || null
}
