import React, { useState, useEffect } from 'react';
import { Users, Calendar, FileText, TrendingUp, Clock, AlertTriangle } from 'lucide-react';
import { useAuth } from '@/contexts/AuthContext';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/Card';
import { Badge } from '@/components/ui/Badge';
import { dashboardApi } from '@/lib/api';
import { formatDate, formatDateTime } from '@/lib/utils';

interface DashboardStats {
  totalPatients: number;
  totalAppointments: number;
  totalEvolutions: number;
  completedAppointments: number;
}

interface UpcomingAppointment {
  id: number;
  patient_name: string;
  professional_name: string;
  date: string;
  time: string;
  therapy_type: string;
}

interface Alert {
  id: number;
  type: 'info' | 'warning' | 'danger';
  message: string;
  created_at: string;
}

export function Dashboard() {
  const { user } = useAuth();
  const [stats, setStats] = useState<DashboardStats>({
    totalPatients: 0,
    totalAppointments: 0,
    totalEvolutions: 0,
    completedAppointments: 0,
  });
  const [upcomingAppointments, setUpcomingAppointments] = useState<UpcomingAppointment[]>([]);
  const [alerts, setAlerts] = useState<Alert[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const fetchDashboardData = async () => {
      try {
        const [statsResponse, appointmentsResponse, alertsResponse] = await Promise.all([
          dashboardApi.getStats(),
          dashboardApi.getUpcomingAppointments(),
          dashboardApi.getAlerts(),
        ]);

        setStats(statsResponse.data.data);
        setUpcomingAppointments(appointmentsResponse.data.data);
        setAlerts(alertsResponse.data.data);
      } catch (error) {
        console.error('Error fetching dashboard data:', error);
      } finally {
        setLoading(false);
      }
    };

    fetchDashboardData();
  }, []);

  if (loading) {
    return (
      <div className="flex items-center justify-center h-64">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
      </div>
    );
  }

  const statCards = [
    {
      title: 'Total de Pacientes',
      value: stats.totalPatients,
      icon: Users,
      color: 'text-blue-600',
      bgColor: 'bg-blue-100',
    },
    {
      title: 'Agendamentos',
      value: stats.totalAppointments,
      icon: Calendar,
      color: 'text-green-600',
      bgColor: 'bg-green-100',
    },
    {
      title: 'Evoluções',
      value: stats.totalEvolutions,
      icon: FileText,
      color: 'text-purple-600',
      bgColor: 'bg-purple-100',
    },
    {
      title: 'Atendimentos Concluídos',
      value: stats.completedAppointments,
      icon: TrendingUp,
      color: 'text-orange-600',
      bgColor: 'bg-orange-100',
    },
  ];

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-bold text-gray-900">
          Bem-vindo, {user?.name}!
        </h1>
        <p className="text-gray-600">
          {user?.type === 'admin' ? 'Painel administrativo' : 
           user?.type === 'assistant' ? 'Painel da assistência' : 
           'Seus dados e agenda'}
        </p>
      </div>

      {/* Stats Cards */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        {statCards.map((stat) => (
          <Card key={stat.title}>
            <CardContent className="p-6">
              <div className="flex items-center">
                <div className={`${stat.bgColor} p-3 rounded-lg`}>
                  <stat.icon className={`h-6 w-6 ${stat.color}`} />
                </div>
                <div className="ml-4">
                  <p className="text-sm font-medium text-gray-600">{stat.title}</p>
                  <p className="text-2xl font-bold text-gray-900">{stat.value}</p>
                </div>
              </div>
            </CardContent>
          </Card>
        ))}
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {/* Upcoming Appointments */}
        <Card>
          <CardHeader>
            <CardTitle className="flex items-center">
              <Clock className="h-5 w-5 mr-2" />
              Próximos Atendimentos
            </CardTitle>
          </CardHeader>
          <CardContent>
            {upcomingAppointments.length === 0 ? (
              <p className="text-gray-500 text-center py-4">
                Nenhum atendimento agendado
              </p>
            ) : (
              <div className="space-y-4">
                {upcomingAppointments.slice(0, 5).map((appointment) => (
                  <div
                    key={appointment.id}
                    className="flex items-center justify-between p-3 bg-gray-50 rounded-lg"
                  >
                    <div>
                      <p className="font-medium text-gray-900">
                        {appointment.patient_name}
                      </p>
                      <p className="text-sm text-gray-600">
                        {appointment.professional_name} • {appointment.therapy_type}
                      </p>
                      <p className="text-sm text-gray-500">
                        {formatDate(appointment.date)} às {appointment.time}
                      </p>
                    </div>
                  </div>
                ))}
              </div>
            )}
          </CardContent>
        </Card>

        {/* Alerts */}
        <Card>
          <CardHeader>
            <CardTitle className="flex items-center">
              <AlertTriangle className="h-5 w-5 mr-2" />
              Alertas e Notificações
            </CardTitle>
          </CardHeader>
          <CardContent>
            {alerts.length === 0 ? (
              <p className="text-gray-500 text-center py-4">
                Nenhum alerta no momento
              </p>
            ) : (
              <div className="space-y-3">
                {alerts.slice(0, 5).map((alert) => (
                  <div
                    key={alert.id}
                    className="flex items-start space-x-3 p-3 bg-gray-50 rounded-lg"
                  >
                    <Badge
                      variant={
                        alert.type === 'danger' ? 'danger' :
                        alert.type === 'warning' ? 'warning' : 'info'
                      }
                      size="sm"
                    >
                      {alert.type === 'danger' ? 'Urgente' :
                       alert.type === 'warning' ? 'Atenção' : 'Info'}
                    </Badge>
                    <div className="flex-1">
                      <p className="text-sm text-gray-700">{alert.message}</p>
                      <p className="text-xs text-gray-500">
                        {formatDateTime(alert.created_at)}
                      </p>
                    </div>
                  </div>
                ))}
              </div>
            )}
          </CardContent>
        </Card>
      </div>
    </div>
  );
}