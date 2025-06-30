<template>
  <div class="medication-label-container">
    <!-- Print Button -->
    <div class="no-print mb-3">
      <button @click="printLabel" class="btn btn-primary">
        <i class="fas fa-print me-2"></i>Print Medication Label
      </button>
    </div>

    <!-- Medication Label -->
    <div class="medication-label" ref="labelContent">
      <div class="label-border">
        <!-- Header -->
        <div class="label-header">
          <h2 class="clinic-name">KLINIK HIDUP SIHAT</h2>
          <div class="clinic-details">
            8, Jalan 2, Taman Sri Jarbu, 43000 Kajang, Selangor.<br>
            Tel: 03-8740 0678
          </div>
        </div>

        <!-- Content -->
        <div class="label-content">
          <div class="row-item">
            <span class="label-text">Nama :</span>
            <div class="field-box filled">{{ patientName }}</div>
          </div>

          <div class="row-item">
            <span class="label-text">Tarikh :</span>
            <div class="field-box filled">{{ currentDate }}</div>
          </div>

          <div class="row-item">
            <span class="label-text">Bil :</span>
            <div class="field-box"></div>
          </div>

          <div class="row-item">
            <span class="label-text">Ubat :</span>
            <div class="field-box"></div>
          </div>

          <div class="checkbox-section">
            <span class="label-text">SEBELUM SELEPAS MAKAN</span>
            <div class="checkbox-container">
              <div class="checkbox-item">
                <div class="checkbox"></div>
                <span>Kal Sehari</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'MedicationLabel',
  props: {
    patientName: {
      type: String,
      required: true
    }
  },
  computed: {
    currentDate() {
      const today = new Date();
      const day = today.getDate().toString().padStart(2, '0');
      const month = (today.getMonth() + 1).toString().padStart(2, '0');
      const year = today.getFullYear();
      return `${day}/${month}/${year}`;
    }
  },
  methods: {
    printLabel() {
      const printContent = this.$refs.labelContent.innerHTML;
      const printWindow = window.open('', '_blank');
      
      printWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
          <title>Medication Label - ${this.patientName}</title>
          <style>
            * {
              margin: 0;
              padding: 0;
              box-sizing: border-box;
            }
            
            body {
              font-family: Arial, sans-serif;
              margin: 10mm;
            }
            
            .medication-label {
              width: 85mm;
              height: 55mm;
              border: 2px solid #000;
              padding: 3mm;
              background: white;
              font-size: 9pt;
              line-height: 1.1;
            }
            
            .label-border {
              height: 100%;
              border: 1px solid #000;
              padding: 2mm;
            }
            
            .label-header {
              text-align: center;
              border-bottom: 1px solid #000;
              padding-bottom: 2mm;
              margin-bottom: 2mm;
            }
            
            .clinic-name {
              font-size: 12pt;
              font-weight: bold;
              margin-bottom: 1mm;
              letter-spacing: 0.5px;
            }
            
            .clinic-details {
              font-size: 7pt;
              line-height: 1.2;
            }
            
            .label-content {
              height: calc(100% - 15mm);
            }
            
            .row-item {
              display: flex;
              align-items: center;
              margin-bottom: 1.5mm;
              height: 4.5mm;
            }
            
            .label-text {
              font-size: 8pt;
              font-weight: bold;
              width: 15mm;
              flex-shrink: 0;
            }
            
            .field-box {
              flex: 1;
              height: 4mm;
              border: 1px solid #000;
              margin-left: 2mm;
              padding: 0 1mm;
              display: flex;
              align-items: center;
              font-size: 8pt;
            }
            
            .field-box.filled {
              background-color: transparent;
              font-weight: bold;
            }
            
            .checkbox-section {
              margin-top: 2mm;
              display: flex;
              align-items: center;
              flex-wrap: wrap;
            }
            
            .checkbox-section .label-text {
              font-size: 7pt;
              width: auto;
              margin-right: 3mm;
            }
            
            .checkbox-container {
              display: flex;
              gap: 5mm;
            }
            
            .checkbox-item {
              display: flex;
              align-items: center;
              gap: 1mm;
            }
            
            .checkbox {
              width: 3mm;
              height: 3mm;
              border: 1px solid #000;
              flex-shrink: 0;
            }
            
            .checkbox-item span {
              font-size: 7pt;
            }
            
            @media print {
              body {
                margin: 0;
              }
              
              .medication-label {
                margin: 0;
                width: 85mm;
                height: 55mm;
              }
            }
          </style>
        </head>
        <body>
          ${printContent}
        </body>
        </html>
      `);
      
      printWindow.document.close();
      
      setTimeout(() => {
        printWindow.print();
        printWindow.close();
      }, 250);
    }
  }
};
</script>

<style scoped>
.medication-label-container {
  max-width: 400px;
}

.medication-label {
  width: 85mm;
  height: 55mm;
  border: 2px solid #000;
  padding: 3mm;
  background: white;
  font-size: 9pt;
  line-height: 1.1;
  font-family: Arial, sans-serif;
}

.label-border {
  height: 100%;
  border: 1px solid #000;
  padding: 2mm;
}

.label-header {
  text-align: center;
  border-bottom: 1px solid #000;
  padding-bottom: 2mm;
  margin-bottom: 2mm;
}

.clinic-name {
  font-size: 12pt;
  font-weight: bold;
  margin-bottom: 1mm;
  letter-spacing: 0.5px;
}

.clinic-details {
  font-size: 7pt;
  line-height: 1.2;
}

.label-content {
  height: calc(100% - 15mm);
}

.row-item {
  display: flex;
  align-items: center;
  margin-bottom: 1.5mm;
  height: 4.5mm;
}

.label-text {
  font-size: 8pt;
  font-weight: bold;
  width: 15mm;
  flex-shrink: 0;
}

.field-box {
  flex: 1;
  height: 4mm;
  border: 1px solid #000;
  margin-left: 2mm;
  padding: 0 1mm;
  display: flex;
  align-items: center;
  font-size: 8pt;
}

.field-box.filled {
  background-color: transparent;
  font-weight: bold;
}

.checkbox-section {
  margin-top: 2mm;
  display: flex;
  align-items: center;
  flex-wrap: wrap;
}

.checkbox-section .label-text {
  font-size: 7pt;
  width: auto;
  margin-right: 3mm;
}

.checkbox-container {
  display: flex;
  gap: 5mm;
}

.checkbox-item {
  display: flex;
  align-items: center;
  gap: 1mm;
}

.checkbox {
  width: 3mm;
  height: 3mm;
  border: 1px solid #000;
  flex-shrink: 0;
}

.checkbox-item span {
  font-size: 7pt;
}

.no-print {
  margin-bottom: 10px;
}

@media print {
  .no-print {
    display: none !important;
  }
  
  .medication-label {
    margin: 0;
    width: 85mm;
    height: 55mm;
  }
}
</style> 