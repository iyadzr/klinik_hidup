import axios from 'axios';

class ConsultationService {
  async getAllConsultations() {
    return axios.get('/api/consultations');
  }

  async getConsultation(id) {
    return axios.get(`/api/consultations/${id}`);
  }

  async updateConsultation(id, data) {
    return axios.put(`/api/consultations/${id}`, data);
  }

  async createConsultation(data) {
    return axios.post('/api/consultations', data);
  }
}

export default new ConsultationService(); 