import { createContext, useCallback, useContext, useMemo, useState } from 'react'
import * as Toast from '@radix-ui/react-toast'

export type ToastVariant = 'success' | 'error' | 'info'

export type ToastMessage = {
  id: string
  variant: ToastVariant
  title: string
  description?: string
  durationMs?: number
}

type ToastContextValue = {
  pushToast: (toast: Omit<ToastMessage, 'id'>) => void
}

const ToastContext = createContext<ToastContextValue | null>(null)

function variantStyles(variant: ToastVariant): {
  border: string
  background: string
  color: string
} {
  switch (variant) {
    case 'success':
      return {
        border: 'var(--green-7)',
        background: 'var(--green-2)',
        color: 'var(--green-11)',
      }
    case 'error':
      return {
        border: 'var(--red-7)',
        background: 'var(--red-2)',
        color: 'var(--red-11)',
      }
    default:
      return {
        border: 'var(--gray-7)',
        background: 'var(--gray-2)',
        color: 'var(--gray-12)',
      }
  }
}

export function ToastProvider({ children }: { children: React.ReactNode }) {
  const [toasts, setToasts] = useState<ToastMessage[]>([])

  const pushToast = useCallback((toast: Omit<ToastMessage, 'id'>) => {
    const id = `${Date.now()}-${Math.random().toString(16).slice(2)}`
    setToasts((prev) => [...prev, { ...toast, id }])
  }, [])

  const value = useMemo(() => ({ pushToast }), [pushToast])

  return (
    <ToastContext.Provider value={value}>
      <Toast.Provider swipeDirection="right">
        {children}
        {toasts.map((t) => {
          const styles = variantStyles(t.variant)
          return (
            <Toast.Root
              key={t.id}
              duration={t.durationMs ?? 4000}
              onOpenChange={(open) => {
                if (!open) {
                  setToasts((prev) => prev.filter((x) => x.id !== t.id))
                }
              }}
              aria-live="off"
              aria-atomic="false"
              style={{
                border: `1px solid ${styles.border}`,
                background: styles.background,
                color: styles.color,
                borderRadius: 10,
                padding: '12px 14px',
                boxShadow: '0 10px 30px rgba(0,0,0,0.15)',
                maxWidth: 420,
              }}
            >
              <Toast.Title style={{ fontWeight: 600, marginBottom: t.description ? 4 : 0 }}>
                {t.title}
              </Toast.Title>
              {t.description && (
                <Toast.Description style={{ opacity: 0.9 }}>{t.description}</Toast.Description>
              )}
            </Toast.Root>
          )
        })}
        <Toast.Viewport
          style={{
            position: 'fixed',
            right: 16,
            bottom: 16,
            display: 'flex',
            flexDirection: 'column',
            gap: 10,
            width: 420,
            maxWidth: 'calc(100vw - 32px)',
            zIndex: 99999,
            outline: 'none',
          }}
        />
      </Toast.Provider>
    </ToastContext.Provider>
  )
}

export function useToast() {
  const ctx = useContext(ToastContext)
  if (!ctx) {
    throw new Error('useToast must be used within ToastProvider')
  }
  return ctx
}
