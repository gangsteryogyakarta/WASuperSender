"use client"

import { useState } from "react"
import { useRouter } from "next/navigation"
import { Lock, Mail, Loader2 } from "lucide-react"
import { toast } from "sonner"
import api from "@/lib/axios" // Use our configured axios instance

import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from "@/components/ui/card"

export default function LoginPage() {
  const router = useRouter()
  const [isLoading, setIsLoading] = useState(false)
  const [formData, setFormData] = useState({
    email: "",
    password: "",
  })

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()
    setIsLoading(true)

    try {
      // 1. Call API login
      const response = await api.post("/api/login", formData)
      
      // 2. Store token (Simple localStorage for now, better in cookies via server action if we had more time)
      const token = response.data.token
      localStorage.setItem("token", token)
      
      // 3. Set default header for subsequent requests
      api.defaults.headers.common['Authorization'] = `Bearer ${token}`

      toast.success("Login successful!", {
        description: "Redirecting to dashboard...",
      })

      // 4. Redirect
      router.push("/dashboard")
      router.refresh()
    } catch (error: any) {
      console.error(error)
      toast.error("Login failed", {
        description: error.response?.data?.message || "Invalid credentials. Please try again.",
      })
    } finally {
      setIsLoading(false)
    }
  }

  return (
    <Card className="w-full max-w-md">
      <CardHeader className="space-y-1">
        <CardTitle className="text-2xl font-bold">WhatsApp CRM</CardTitle>
        <CardDescription>
          Enter your email and password to access your dashboard
        </CardDescription>
      </CardHeader>
      <form onSubmit={handleSubmit}>
        <CardContent className="space-y-4">
          <div className="space-y-2">
            <Label htmlFor="email">Email</Label>
            <div className="relative">
              <Mail className="absolute left-3 top-2.5 h-4 w-4 text-muted-foreground" />
              <Input
                id="email"
                type="email"
                placeholder="sales@astra.co.id"
                required
                className="pl-9"
                value={formData.email}
                onChange={(e) => setFormData({ ...formData, email: e.target.value })}
                disabled={isLoading}
              />
            </div>
          </div>
          <div className="space-y-2">
            <Label htmlFor="password">Password</Label>
            <div className="relative">
              <Lock className="absolute left-3 top-2.5 h-4 w-4 text-muted-foreground" />
              <Input
                id="password"
                type="password"
                required
                className="pl-9"
                value={formData.password}
                onChange={(e) => setFormData({ ...formData, password: e.target.value })}
                disabled={isLoading}
              />
            </div>
          </div>
        </CardContent>
        <CardFooter>
          <Button className="w-full" type="submit" disabled={isLoading}>
            {isLoading && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
            Sign In
          </Button>
        </CardFooter>
      </form>
    </Card>
  )
}
