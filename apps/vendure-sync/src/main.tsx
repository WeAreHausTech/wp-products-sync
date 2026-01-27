import React from 'react'
import ReactDOM from 'react-dom/client'
import App from './App'
import './index.css'
import '@radix-ui/themes/styles.css'
import { Theme } from '@radix-ui/themes'
import { QueryClient, QueryClientProvider } from '@tanstack/react-query'

const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      retry: false,
      staleTime: 30_000,
      refetchOnWindowFocus: false,
    },
    mutations: {
      retry: false,
    },
  },
})

const containerId = 'vendure-sync-root'
const container = document.getElementById(containerId)

if (container) {
  ReactDOM.createRoot(container).render(
    <React.StrictMode>
      <QueryClientProvider client={queryClient}>
        <Theme accentColor="ruby" grayColor="slate" panelBackground="solid">
          <App />
        </Theme>
      </QueryClientProvider>
    </React.StrictMode>,
  )
} else {
  console.error(`Container element with id "${containerId}" not found`)
}
