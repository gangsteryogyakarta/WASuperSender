"use client"

import { useEffect, useState } from "react"
import { Plus, Play, Pause, Trash2 } from "lucide-react"
import { Button } from "@/components/ui/button"
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card"
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table"
import api from "@/lib/axios"
import { format } from "date-fns"
import { toast } from "sonner"

interface Campaign {
  id: string
  name: string
  status: string
  scheduled_at: string | null
  sent_count: number
  total_recipients: number
  created_at: string
}

export default function CampaignsPage() {
  const [campaigns, setCampaigns] = useState<Campaign[]>([])
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    fetchCampaigns()
  }, [])

  const fetchCampaigns = async () => {
    try {
      setLoading(true)
      const response = await api.get("/api/campaigns")
      setCampaigns(response.data.data || [])
    } catch (error) {
      console.error("Failed to fetch campaigns", error)
    } finally {
      setLoading(false)
    }
  }

  const handleAction = async (id: string, action: 'start' | 'pause' | 'resume') => {
      try {
          await api.post(`/api/campaigns/${id}/${action}`)
          toast.success(`Campaign ${action}ed successfully`)
          fetchCampaigns()
      } catch (error) {
          toast.error(`Failed to ${action} campaign`)
      }
  }

  const getStatusColor = (status: string) => {
    const colors: Record<string, string> = {
      draft: "bg-gray-100 text-gray-800",
      scheduled: "bg-blue-100 text-blue-800",
      running: "bg-yellow-100 text-yellow-800",
      paused: "bg-orange-100 text-orange-800",
      completed: "bg-green-100 text-green-800",
      failed: "bg-red-100 text-red-800",
    }
    return colors[status] || "bg-gray-100 text-gray-800"
  }

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h2 className="text-3xl font-bold tracking-tight">Campaigns</h2>
          <p className="text-muted-foreground">Manage and monitor your broadcast campaigns.</p>
        </div>
        <Button>
          <Plus className="mr-2 h-4 w-4" />
          New Campaign
        </Button>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>All Campaigns</CardTitle>
        </CardHeader>
        <CardContent>
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Name</TableHead>
                <TableHead>Status</TableHead>
                <TableHead>Progress</TableHead>
                <TableHead>Schedule</TableHead>
                <TableHead className="text-right">Actions</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {loading ? (
                <TableRow>
                  <TableCell colSpan={5} className="text-center h-24">
                    Loading campaigns...
                  </TableCell>
                </TableRow>
              ) : campaigns.length === 0 ? (
                <TableRow>
                  <TableCell colSpan={5} className="text-center h-24">
                    No campaigns found.
                  </TableCell>
                </TableRow>
              ) : (
                campaigns.map((campaign) => (
                  <TableRow key={campaign.id}>
                    <TableCell className="font-medium">{campaign.name}</TableCell>
                    <TableCell>
                      <span className={`inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ${getStatusColor(campaign.status)}`}>
                        {campaign.status}
                      </span>
                    </TableCell>
                    <TableCell>
                      <div className="flex items-center gap-2">
                        <div className="h-2 w-24 rounded-full bg-secondary">
                          <div 
                            className="h-full rounded-full bg-primary" 
                            style={{ width: `${(campaign.sent_count / (campaign.total_recipients || 1)) * 100}%` }}
                          />
                        </div>
                        <span className="text-xs text-muted-foreground">
                            {campaign.sent_count}/{campaign.total_recipients}
                        </span>
                      </div>
                    </TableCell>
                    <TableCell>
                        {campaign.scheduled_at 
                            ? format(new Date(campaign.scheduled_at), "MMM d, HH:mm") 
                            : "-"}
                    </TableCell>
                    <TableCell className="text-right">
                      {campaign.status === 'draft' || campaign.status === 'paused' ? (
                          <Button variant="ghost" size="icon" onClick={() => handleAction(campaign.id, 'start')}>
                            <Play className="h-4 w-4 text-green-600" />
                          </Button>
                      ) : campaign.status === 'running' ? (
                          <Button variant="ghost" size="icon" onClick={() => handleAction(campaign.id, 'pause')}>
                            <Pause className="h-4 w-4 text-yellow-600" />
                          </Button>
                      ) : null}
                      
                      <Button variant="ghost" size="icon">
                        <Trash2 className="h-4 w-4 text-red-500" />
                      </Button>
                    </TableCell>
                  </TableRow>
                ))
              )}
            </TableBody>
          </Table>
        </CardContent>
      </Card>
    </div>
  )
}
