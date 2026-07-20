import { createClient } from '@supabase/supabase-js'

const supabaseUrl = import.meta.env.VITE_SUPABASE_URL
const supabaseAnonKey = import.meta.env.VITE_SUPABASE_ANON_KEY

if (!supabaseUrl || !supabaseAnonKey) {
  // Ajuda a apanhar cedo um .env mal configurado
  console.warn(
    '[supabase] VITE_SUPABASE_URL / VITE_SUPABASE_ANON_KEY em falta — copia .env.example para .env e preenche.'
  )
}

export const supabase = createClient(supabaseUrl, supabaseAnonKey)
