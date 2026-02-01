"use client"

import Link from "next/link"
import { usePathname, useRouter } from "next/navigation"
import { BarChart3, Users, Send, Settings, LogOut, LayoutDashboard } from "lucide-react"
import { cn } from "@/lib/utils"
import { Button } from "@/components/ui/button"
import api from "@/lib/axios"
import { toast } from "sonner"

const sidebarItems = [
  { icon: LayoutDashboard, label: "Dashboard", href: "/dashboard" },
  { icon: Users, label: "Contacts", href: "/contacts" },
  { icon: Send, label: "Campaigns", href: "/campaigns" },
  { icon: Settings, label: "Settings", href: "/settings" },
]

export function Sidebar() {
  const pathname = usePathname()
  const router = useRouter()

  const handleLogout = async () => {
    try {
      await api.post('/api/logout')
    } catch (error) {
      console.error("Logout failed", error)
    } finally {
      localStorage.removeItem("token")
      router.push("/login")
      toast.success("Logged out")
    }
  }

  return (
    <div className="flex h-full w-64 flex-col border-r bg-card px-3 py-4">
      <div className="flex items-center gap-2 px-3 py-2">
        <Send className="h-6 w-6 text-primary" />
        <span className="text-lg font-bold">WA CRM Astra</span>
      </div>
      <div className="mt-8 flex flex-1 flex-col gap-1">
        {sidebarItems.map((item) => (
          <Link
            key={item.href}
            href={item.href}
            className={cn(
              "flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition-colors hover:bg-accent",
              pathname.startsWith(item.href) 
                ? "bg-primary/10 text-primary" 
                : "text-muted-foreground"
            )}
          >
            <item.icon className="h-4 w-4" />
            {item.label}
          </Link>
        ))}
      </div>
      <div className="border-t pt-4">
        <Button variant="ghost" className="w-full justify-start gap-3" onClick={handleLogout}>
          <LogOut className="h-4 w-4" />
          Logout
        </Button>
      </div>
    </div>
  )
}
