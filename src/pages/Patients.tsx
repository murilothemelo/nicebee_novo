import React, { useState, useEffect } from 'react';
import { Plus, Search, Edit, Trash2, FolderOpen, Upload } from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/Card';
import { Button } from '@/components/ui/Button';
import { Input } from '@/components/ui/Input';
import { Select } from '@/components/ui/Select';
import { Modal } from '@/components/ui/Modal';
import { Badge } from '@/components/ui/Badge';
import { Textarea } from '@/components/ui/Textarea';
import { patientsApi, insurancePlansApi } from '@/lib/api';
import { Patient, InsurancePlan, MedicalRecord } from '@/types';
import { formatDate, getGenderLabel } from '@/lib/utils';
import { useAuth } from '@/contexts/AuthContext';

interface PatientFormData {
  name: string;
  birth_date: string;
  category: string;
  gender: 'M' | 'F' | 'Other';
  phone: string;
  email: string;
  address: string;
  insurance_plan_id: string;
}

interface MedicalRecordFormData {
  type: 'document' | 'report' | 'evaluation' | 'other';
  title: string;
  description: string;
  file: File | null;
}

export function Patients() {
  const { user } = useAuth();
  const [patients, setPatients] = useState<Patient[]>([]);
  const [filteredPatients, setFilteredPatients] = useState<Patient[]>([]);
  const [insurancePlans, setInsurancePlans] = useState<InsurancePlan[]>([]);
  const [medicalRecords, setMedicalRecords] = useState<MedicalRecord[]>([]);
  const [loading, setLoading] = useState(true);
  const [searchTerm, setSearchTerm] = useState('');
  const [categoryFilter, setCategoryFilter] = useState('');
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [isMedicalRecordModalOpen, setIsMedicalRecordModalOpen] = useState(false);
  const [editingPatient, setEditingPatient] = useState<Patient | null>(null);
  const [selectedPatient, setSelectedPatient] = useState<Patient | null>(null);
  const [formData, setFormData] = useState<PatientFormData>({
    name: '',
    birth_date: '',
    category: '',
    gender: 'M',
    phone: '',
    email: '',
    address: '',
    insurance_plan_id: '',
  });
  const [medicalRecordFormData, setMedicalRecordFormData] = useState<MedicalRecordFormData>({
    type: 'document',
    title: '',
    description: '',
    file: null,
  });

  useEffect(() => {
    Promise.all([fetchPatients(), fetchInsurancePlans()]);
  }, []);

  useEffect(() => {
    filterPatients();
  }, [patients, searchTerm, categoryFilter]);

  const fetchPatients = async () => {
    try {
      const response = await patientsApi.getAll();
      setPatients(response.data.data);
    } catch (error) {
      console.error('Error fetching patients:', error);
    } finally {
      setLoading(false);
    }
  };

  const fetchInsurancePlans = async () => {
    try {
      const response = await insurancePlansApi.getAll();
      setInsurancePlans(response.data.data);
    } catch (error) {
      console.error('Error fetching insurance plans:', error);
    }
  };

  const fetchMedicalRecords = async (patientId: number) => {
    try {
      const response = await patientsApi.getMedicalRecords(patientId);
      setMedicalRecords(response.data.data);
    } catch (error) {
      console.error('Error fetching medical records:', error);
    }
  };

  const filterPatients = () => {
    let filtered = patients;

    if (searchTerm) {
      filtered = filtered.filter(patient =>
        patient.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
        patient.category.toLowerCase().includes(searchTerm.toLowerCase())
      );
    }

    if (categoryFilter) {
      filtered = filtered.filter(patient => patient.category === categoryFilter);
    }

    setFilteredPatients(filtered);
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    try {
      const dataToSend = {
        ...formData,
        insurance_plan_id: formData.insurance_plan_id ? parseInt(formData.insurance_plan_id) : null,
      };

      if (editingPatient) {
        await patientsApi.update(editingPatient.id, dataToSend);
      } else {
        await patientsApi.create(dataToSend);
      }
      await fetchPatients();
      handleCloseModal();
    } catch (error) {
      console.error('Error saving patient:', error);
    }
  };

  const handleMedicalRecordSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!selectedPatient || !medicalRecordFormData.file) return;

    try {
      const formData = new FormData();
      formData.append('type', medicalRecordFormData.type);
      formData.append('title', medicalRecordFormData.title);
      formData.append('description', medicalRecordFormData.description);
      formData.append('file', medicalRecordFormData.file);

      await patientsApi.uploadMedicalRecord(selectedPatient.id, formData);
      await fetchMedicalRecords(selectedPatient.id);
      handleCloseMedicalRecordModal();
    } catch (error) {
      console.error('Error uploading medical record:', error);
    }
  };

  const handleDelete = async (id: number) => {
    if (window.confirm('Tem certeza que deseja excluir este paciente?')) {
      try {
        await patientsApi.delete(id);
        await fetchPatients();
      } catch (error) {
        console.error('Error deleting patient:', error);
      }
    }
  };

  const handleEdit = (patient: Patient) => {
    setEditingPatient(patient);
    setFormData({
      name: patient.name,
      birth_date: patient.birth_date,
      category: patient.category,
      gender: patient.gender,
      phone: patient.phone || '',
      email: patient.email || '',
      address: patient.address || '',
      insurance_plan_id: patient.insurance_plan_id ? patient.insurance_plan_id.toString() : '',
    });
    setIsModalOpen(true);
  };

  const handleViewMedicalRecords = (patient: Patient) => {
    setSelectedPatient(patient);
    fetchMedicalRecords(patient.id);
    setIsMedicalRecordModalOpen(true);
  };

  const handleCloseModal = () => {
    setIsModalOpen(false);
    setEditingPatient(null);
    setFormData({
      name: '',
      birth_date: '',
      category: '',
      gender: 'M',
      phone: '',
      email: '',
      address: '',
      insurance_plan_id: '',
    });
  };

  const handleCloseMedicalRecordModal = () => {
    setIsMedicalRecordModalOpen(false);
    setSelectedPatient(null);
    setMedicalRecords([]);
    setMedicalRecordFormData({
      type: 'document',
      title: '',
      description: '',
      file: null,
    });
  };

  const categories = [...new Set(patients.map(p => p.category))];

  if (loading) {
    return (
      <div className="flex items-center justify-center h-64">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">Pacientes</h1>
          <p className="text-gray-600">Gerencie os pacientes e prontuários</p>
        </div>
        <Button onClick={() => setIsModalOpen(true)}>
          <Plus className="h-4 w-4 mr-2" />
          Novo Paciente
        </Button>
      </div>

      {/* Filters */}
      <Card>
        <CardContent className="p-6">
          <div className="flex flex-col sm:flex-row gap-4">
            <div className="flex-1">
              <Input
                placeholder="Buscar por nome ou categoria..."
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
                icon={<Search className="h-4 w-4 text-gray-400" />}
              />
            </div>
            <div className="sm:w-48">
              <Select
                value={categoryFilter}
                onChange={(e) => setCategoryFilter(e.target.value)}
                options={[
                  { value: '', label: 'Todas as categorias' },
                  ...categories.map(cat => ({ value: cat, label: cat }))
                ]}
              />
            </div>
          </div>
        </CardContent>
      </Card>

      {/* Patients List */}
      <Card>
        <CardHeader>
          <CardTitle>Pacientes ({filteredPatients.length})</CardTitle>
        </CardHeader>
        <CardContent>
          {filteredPatients.length === 0 ? (
            <p className="text-center text-gray-500 py-8">
              Nenhum paciente encontrado
            </p>
          ) : (
            <div className="overflow-x-auto">
              <table className="w-full">
                <thead>
                  <tr className="border-b border-gray-200">
                    <th className="text-left py-3 px-4 font-medium text-gray-700">Nome</th>
                    <th className="text-left py-3 px-4 font-medium text-gray-700">Categoria</th>
                    <th className="text-left py-3 px-4 font-medium text-gray-700">Gênero</th>
                    <th className="text-left py-3 px-4 font-medium text-gray-700">Nascimento</th>
                    <th className="text-right py-3 px-4 font-medium text-gray-700">Ações</th>
                  </tr>
                </thead>
                <tbody>
                  {filteredPatients.map((patient) => (
                    <tr key={patient.id} className="border-b border-gray-100 hover:bg-gray-50">
                      <td className="py-3 px-4">
                        <div>
                          <p className="font-medium text-gray-900">{patient.name}</p>
                          {patient.phone && (
                            <p className="text-sm text-gray-500">{patient.phone}</p>
                          )}
                        </div>
                      </td>
                      <td className="py-3 px-4">
                        <Badge variant="info">{patient.category}</Badge>
                      </td>
                      <td className="py-3 px-4 text-gray-600">
                        {getGenderLabel(patient.gender)}
                      </td>
                      <td className="py-3 px-4 text-gray-600">
                        {formatDate(patient.birth_date)}
                      </td>
                      <td className="py-3 px-4">
                        <div className="flex items-center justify-end space-x-2">
                          <Button
                            variant="ghost"
                            size="sm"
                            onClick={() => handleViewMedicalRecords(patient)}
                          >
                            <FolderOpen className="h-4 w-4" />
                          </Button>
                          <Button
                            variant="ghost"
                            size="sm"
                            onClick={() => handleEdit(patient)}
                          >
                            <Edit className="h-4 w-4" />
                          </Button>
                          {user?.type !== 'professional' && (
                            <Button
                              variant="ghost"
                              size="sm"
                              onClick={() => handleDelete(patient.id)}
                              className="text-red-600 hover:text-red-700"
                            >
                              <Trash2 className="h-4 w-4" />
                            </Button>
                          )}
                        </div>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}
        </CardContent>
      </Card>

      {/* Patient Modal */}
      <Modal
        isOpen={isModalOpen}
        onClose={handleCloseModal}
        title={editingPatient ? 'Editar Paciente' : 'Novo Paciente'}
        maxWidth="xl"
      >
        <form onSubmit={handleSubmit} className="space-y-4">
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <Input
              label="Nome *"
              value={formData.name}
              onChange={(e) => setFormData({ ...formData, name: e.target.value })}
              required
            />
            <Input
              label="Data de Nascimento *"
              type="date"
              value={formData.birth_date}
              onChange={(e) => setFormData({ ...formData, birth_date: e.target.value })}
              required
            />
          </div>

          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <Input
              label="Categoria *"
              value={formData.category}
              onChange={(e) => setFormData({ ...formData, category: e.target.value })}
              placeholder="Ex: Criança, Adulto, Idoso..."
              required
            />
            <Select
              label="Gênero *"
              value={formData.gender}
              onChange={(e) => setFormData({ ...formData, gender: e.target.value as any })}
              options={[
                { value: 'M', label: 'Masculino' },
                { value: 'F', label: 'Feminino' },
                { value: 'Other', label: 'Outro' },
              ]}
            />
          </div>

          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <Input
              label="Telefone"
              value={formData.phone}
              onChange={(e) => setFormData({ ...formData, phone: e.target.value })}
              placeholder="(00) 00000-0000"
            />
            <Input
              label="E-mail"
              type="email"
              value={formData.email}
              onChange={(e) => setFormData({ ...formData, email: e.target.value })}
            />
          </div>

          <Textarea
            label="Endereço"
            value={formData.address}
            onChange={(e) => setFormData({ ...formData, address: e.target.value })}
            rows={3}
          />

          <Select
            label="Plano de Saúde"
            value={formData.insurance_plan_id}
            onChange={(e) => setFormData({ ...formData, insurance_plan_id: e.target.value })}
            options={[
              { value: '', label: 'Selecione um plano' },
              ...insurancePlans.map(plan => ({ value: plan.id.toString(), label: plan.name })),
            ]}
          />

          <div className="flex justify-end space-x-3 pt-4">
            <Button variant="outline" onClick={handleCloseModal}>
              Cancelar
            </Button>
            <Button type="submit">
              {editingPatient ? 'Salvar' : 'Criar'}
            </Button>
          </div>
        </form>
      </Modal>

      {/* Medical Records Modal */}
      <Modal
        isOpen={isMedicalRecordModalOpen}
        onClose={handleCloseMedicalRecordModal}
        title={`Prontuário - ${selectedPatient?.name}`}
        maxWidth="2xl"
      >
        <div className="space-y-6">
          <div className="flex justify-between items-center">
            <h3 className="text-lg font-medium">Documentos</h3>
            <Button
              onClick={() => document.getElementById('upload-form')?.scrollIntoView()}
              size="sm"
            >
              <Upload className="h-4 w-4 mr-2" />
              Adicionar Documento
            </Button>
          </div>

          {/* Medical Records List */}
          <div className="space-y-3 max-h-64 overflow-y-auto">
            {medicalRecords.length === 0 ? (
              <p className="text-gray-500 text-center py-4">
                Nenhum documento encontrado
              </p>
            ) : (
              medicalRecords.map((record) => (
                <div key={record.id} className="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                  <div>
                    <p className="font-medium text-gray-900">{record.title}</p>
                    <p className="text-sm text-gray-600">{record.description}</p>
                    <div className="flex items-center space-x-3 text-xs text-gray-500 mt-1">
                      <Badge size="sm">{record.type}</Badge>
                      <span>{formatDate(record.created_at)}</span>
                    </div>
                  </div>
                  <Button variant="outline" size="sm">
                    Baixar
                  </Button>
                </div>
              ))
            )}
          </div>

          {/* Upload Form */}
          <div id="upload-form" className="border-t pt-6">
            <h4 className="text-md font-medium mb-4">Adicionar Documento</h4>
            <form onSubmit={handleMedicalRecordSubmit} className="space-y-4">
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <Select
                  label="Tipo de Documento *"
                  value={medicalRecordFormData.type}
                  onChange={(e) => setMedicalRecordFormData({ ...medicalRecordFormData, type: e.target.value as any })}
                  options={[
                    { value: 'document', label: 'Documento' },
                    { value: 'report', label: 'Laudo' },
                    { value: 'evaluation', label: 'Avaliação' },
                    { value: 'other', label: 'Outro' },
                  ]}
                />
                <Input
                  label="Título *"
                  value={medicalRecordFormData.title}
                  onChange={(e) => setMedicalRecordFormData({ ...medicalRecordFormData, title: e.target.value })}
                  required
                />
              </div>

              <Textarea
                label="Descrição"
                value={medicalRecordFormData.description}
                onChange={(e) => setMedicalRecordFormData({ ...medicalRecordFormData, description: e.target.value })}
                rows={3}
              />

              <Input
                label="Arquivo *"
                type="file"
                onChange={(e) => setMedicalRecordFormData({ ...medicalRecordFormData, file: e.target.files?.[0] || null })}
                accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"
                required
              />

              <div className="flex justify-end space-x-3">
                <Button variant="outline" onClick={handleCloseMedicalRecordModal}>
                  Fechar
                </Button>
                <Button type="submit">
                  Adicionar Documento
                </Button>
              </div>
            </form>
          </div>
        </div>
      </Modal>
    </div>
  );
}