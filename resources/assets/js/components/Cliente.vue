<template>
  <main class="main">
    <div class="loading-overlay" v-if="isLoading">
      <div class="loading-container">
        <div class="spinner"></div>
        <div class="loading-text">LOADING...</div>
      </div>
    </div>
    <Panel :toggleable="false" class="cliente-panel">
      <template #header>
        <div class="panel-header">
          <i class="pi pi-users panel-icon"></i>
          <h4 class="panel-title">CLIENTES</h4>
        </div>
      </template>
      <div class="toolbar-container" style="margin-top: 0; padding-top: 0;">
        <div class="search-bar">
          <div class="p-inputgroup">
            <span class="p-inputgroup-addon">
              <i class="pi pi-search"></i>
            </span>
            <InputText v-model="buscar" placeholder="Buscar en todos los campos..." class="p-inputtext-sm"
              @input="listarPersona(buscar, criterio)" />
            <Button icon="pi pi-refresh" class="p-button-help p-button-sm" @click="resetBuscar"
              v-tooltip="'Limpiar búsqueda'" />
          </div>
        </div>
        <div class="toolbar">
          <Button icon="pi pi-plus" :label="mostrarLabel ? 'Nuevo' : ''" class="p-button-secondary p-button-sm"
            @click="abrirModal('persona', 'registrar')" />
        </div>
      </div>

      <DataTable :value="arrayPersona" class="p-datatable-gridlines p-datatable-sm" :paginator="true" :rows="8"
        :rowsPerPageOptions="[8, 16, 24]" responsiveLayout="scroll">
        <Column header="Acciones" style="width: 100px;">
          <template #body="slotProps">
            <Button icon="pi pi-pencil" class="p-button-warning p-button-sm"
              style="padding: 0.3rem 0.4rem; font-size: 0.75rem; width: auto; min-width: unset;"
              @click="abrirModal('persona', 'actualizar', slotProps.data)" />
          </template>
        </Column>
        <Column field="nombre" header="Nombres" class="d-none d-md-table-cell"></Column>
        <Column header="Documento de Identidad" class="d-none d-md-table-cell">
          <template #body="slotProps">
            {{ getDocumentoIdentidad(slotProps.data) }}
          </template>
        </Column>
  <Column header="Teléfono">
            <template #body="slotProps">
              <span v-if="formatTelefono(slotProps.data).existe">
                {{ formatTelefono(slotProps.data).texto }}
              </span>
              <span
                v-else
                class="text-red-500 flex items-center gap-1"
                style="color: rgb(231, 76, 60)"
              >
                <i class="pi pi-exclamation-triangle"></i>
                {{ formatTelefono(slotProps.data).texto }}
              </span>
            </template>
          </Column>   </DataTable>
    </Panel>

    <Dialog :visible.sync="modal" :containerStyle="dialogContainerStyle" :modal="true" class="responsive-dialog">
      <template #header>
        <h3>{{ tituloModal }}</h3>
      </template>

      <form @submit.prevent="enviarFormulario">
        <div class="p-fluid form-compact">
          <div class="p-field input-container">
            <label for="nombre" class="required-field">
              <span class="required-icon">*</span>
              Nombre del cliente
            </label>
            <InputText id="nombre" v-model="datosFormulario.nombre" :class="{ 'p-invalid': errores.nombre }"
              @input="validarCampo('nombre')" required autofocus />
            <small class="p-error error-message" v-if="errores.nombre">
              <strong>{{ errores.nombre }}</strong>
            </small>
          </div>

          <div class="p-field input-container">
            <label for="tipo_documento" class="required-field">
              <span class="required-icon">*</span>
              Tipo de documento
            </label>
            <Dropdown id="tipo_documento" v-model="datosFormulario.tipo_documento" :options="tipoDocumentoOptions"
              optionLabel="label" optionValue="value" placeholder="Selecciona un tipo de documento"
              :class="{ 'p-invalid': errores.tipo_documento }" @change="validarCampo('tipo_documento')" />
            <small class="p-error error-message" v-if="errores.tipo_documento">
              <strong>{{ errores.tipo_documento }}</strong>
            </small>
          </div>

          <div class="p-field input-container">
            <label :for="'num_documento_' + datosFormulario.tipo_documento" class="required-field">
              <span class="required-icon">*</span>
              N° {{ getTipoDocumentoLabel() }}
            </label>
            <InputText :id="'num_documento_' + datosFormulario.tipo_documento" v-model="datosFormulario.num_documento"
              :class="{ 'p-invalid': errores.num_documento }" @input="validarCampo('num_documento')" />
            <small class="p-error error-message" v-if="errores.num_documento">
              <strong>{{ errores.num_documento }}</strong>
            </small>
          </div>

          <div class="p-field input-container">
            <label for="complemento" class="optional-field">
              <i class="pi pi-info-circle optional-icon"></i>
              Complemento
              <span class="p-tag p-tag-secondary">Opcional</span>
            </label>
            <div class="p-inputgroup">
              <InputText id="complemento" v-model="datosFormulario.complemento" :disabled="!mostrarComplemento" />
              <Button icon="pi pi-check" @click="mostrarComplemento = !mostrarComplemento" />
            </div>
          </div>

           <div class="p-field input-container">
            <label for="complemento" class="optional-field">
              <i class="pi pi-info-circle optional-icon"></i>
              Teléfono
              <span class="p-tag p-tag-secondary">Opcional</span>
            </label>
            <div class="p-inputgroup">
             <InputText
              id="telefono"
              v-model="datosFormulario.telefono"
            />
            </div>
          </div>

            
        </div>
      </form>

      <template #footer>
        <Button label="Cancelar" icon="pi pi-times" class="p-button-danger p-button-sm modal-button"
          @click="cerrarModal" />
        <Button v-if="tipoAccion == 1" label="Guardar" icon="pi pi-check"
          class="p-button-success p-button-sm modal-button" @click="enviarFormulario" />
        <Button v-if="tipoAccion == 2" label="Actualizar" icon="pi pi-check"
          class="p-button-warning p-button-sm modal-button" @click="enviarFormulario" />
      </template>
    </Dialog>

    <Dialog v-if="modalImportar" :visible.sync="modalImportar" :modal="true">
      <ImportarExcelCliente @cerrar="cerrarModalImportar"></ImportarExcelCliente>
    </Dialog>
  </main>
</template>

<script>
import Swal from 'sweetalert2';
import Card from "primevue/card";
import Panel from "primevue/panel";
import Button from "primevue/button";
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import InputText from "primevue/inputtext";
import Dropdown from "primevue/dropdown";
import AutoComplete from "primevue/autocomplete";
import Dialog from "primevue/dialog";
import Paginator from "primevue/paginator";
import Message from "primevue/message";
import { esquemaCliente } from "../constants/validations";

export default {
  components: {
    Card,
    Panel,
    Button,
    DataTable,
    Column,
    InputText,
    Dropdown,
    AutoComplete,
    Dialog,
    Paginator,
    Message,
  },
  data() {
    return {
      mostrarLabel: true,
      isLoading: false,
      tipoDocumentoOptions: [
        { label: "CI - CEDULA DE IDENTIDAD", value: "1" },
        { label: "CEX - CEDULA DE IDENTIDAD DE EXTRANJERO", value: "2" },
        { label: "NIT - NÚMERO DE IDENTIFICACIÓN TRIBUTARIA", value: "5" },
        { label: "PAS - PASAPORTE", value: "3" },
        { label: "OD - OTRO DOCUMENTO DE IDENTIDAD", value: "4" },
      ],
      criterioOptions: [
        { label: "Búsqueda Global", value: "global" },
        { label: "Nombre", value: "nombre" },
        { label: "Documento", value: "num_documento" },
        { label: "Email", value: "email" },
        { label: "Teléfono", value: "telefono" },
      ],
      datosFormulario: {
        nombre: "",
        tipo_documento: "",
        num_documento: "",
        complemento: "",
      },
      errores: {},
      rolUsuario: "",
      arrayUsuarioV: [],
      usuarioSeleccionado: "",
      idusuario: "",
      arrayDetalleUsuario: [],
      activaredit: false,
      arrayUsuarioFiltro: [],
      usuarioSeleccionadodos: "",
      usuariodos_id: "",
      role_id: "",
      mostrarComplemento: false,
      modalImportar: false,
      arrayPersona: [],
      modal: false,
      tituloModal: "",
      tipoAccion: 0,
      errorPersona: 0,
      errorMostrarMsjPersona: [],
      pagination: {
        total: 0,
        current_page: 0,
        per_page: 0,
        last_page: 0,
        from: 0,
        to: 0,
      },
      criterio: "global",
      buscar: "",
    };
  },
  computed: {
    dialogContainerStyle() {
      if (window.innerWidth <= 480) {
        return { width: "95vw", maxWidth: "95vw", margin: "0 auto" };
      } else if (window.innerWidth <= 768) {
        return { width: "90vw", maxWidth: "90vw", margin: "0 auto" };
      } else if (window.innerWidth <= 1024) {
        return { width: "85vw", maxWidth: "900px", margin: "0 auto" };
      } else {
        return { width: "800px", maxWidth: "90vw", margin: "0 auto" };
      }
    },
  },
  methods: {
       formatTelefono(data) {
      if (data.telefono) {
        return { existe: true, texto: data.telefono };
      }
      return { existe: false, texto: "Teléfono no registrado" };
    },
    toastSuccess(mensaje) {
      this.$toasted.show(
        `
    <div style="height: 60px;font-size:16px;">
        <br>
        ` +
        mensaje +
        `.<br>
    </div>`,
        {
          type: "success",
          position: "bottom-right",
          duration: 2000,
        }
      );
    },
    toastError(mensaje) {
      this.$toasted.show(
        `
    <div style="height: 60px;font-size:16px;">
        <br>
        ` +
        mensaje +
        `<br>
    </div>`,
        {
          type: "error",
          position: "bottom-right",
          duration: 2000,
        }
      );
    },
    resetBuscar() {
      this.buscar = "";
      this.listarPersona(this.buscar, this.criterio);
    },
    handleResize() {
      this.mostrarLabel = window.innerWidth > 768;
    },
    getDocumentoIdentidad(data) {
      const tipoAbreviado = this.getAbreviaturaTipoDocumento(
        data.tipo_documento
      );
      return `${tipoAbreviado} ${data.num_documento}`;
    },
    getAbreviaturaTipoDocumento(tipo) {
      switch (tipo) {
        case "1":
          return "CI";
        case "2":
          return "CEX";
        case "3":
          return "Pas"; // Abreviatura de Pasaporte
        case "5":
          return "NIT";
        default:
          return tipo ? tipo.substring(0, 3).toUpperCase() : ""; // Abreviatura genérica
      }
    },

    getTipoDocumentoLabel() {
      switch (this.datosFormulario.tipo_documento) {
        case "1":
          return "CI";
        case "2":
          return "CEX";
        case "3":
          return "Pasaporte";
        case "5":
          return "NIT";
        default:
          return "Documento";
      }
    },
    onPageChange(event) {
      this.cambiarPagina(event.page + 1, this.buscar, this.criterio);
    },
    async validarCampo(campo) {
      try {
        await esquemaCliente.validateAt(campo, this.datosFormulario);
        this.errores[campo] = null;
      } catch (error) {
        this.errores[campo] = error.message;
      }
    },
    async enviarFormulario() {
      try {
        await esquemaCliente.validate(this.datosFormulario, {
          abortEarly: false,
        });
        if (this.tipoAccion == 1) {
          this.registrarPersona(this.datosFormulario);
        } else {
          this.datosFormulario.usuariodos_id = this.idusuario;
          this.actualizarPersona(this.datosFormulario);
        }
        this.mostrarComplemento = false;
      } catch (error) {
        const erroresValidacion = {};
        error.inner.forEach((e) => {
          erroresValidacion[e.path] = e.message;
        });
        this.errores = erroresValidacion;
      }
    },
    async listarPersona(buscar, criterio) {
      try {
        this.isLoading = true; // Activar loading
        const url = `/cliente?buscar=${buscar}&criterio=${criterio}&usuarioid=${this.usuariodos_id}`;
        const response = await axios.get(url);
        const respuesta = response.data;

        // Ahora 'usuarios' es un array plano
        this.arrayPersona = respuesta.usuarios;

      } catch (error) {
        console.error("Error al listar clientes:", error);
        this.$toast.add({
          severity: "error",
          summary: "Error",
          detail: "No se pudieron cargar los clientes",
          life: 3000,
        });
      } finally {
        setTimeout(() => {
          this.isLoading = false; // Desactivar loading
        }, 500);
      }
    },
    async selectUsuarioFiltro(event) {
      try {
        this.isLoading = true; // Activar loading
        const url = "/cliente/usuario/filtro?filtro=" + event.query;
        const response = await axios.get(url);
        this.arrayUsuarioFiltro = response.data.usuariodos;
      } catch (error) {
        console.error("Error al filtrar usuarios:", error);
        this.$toast.add({
          severity: "error",
          summary: "Error",
          detail: "Error al filtrar usuarios",
          life: 3000,
        });
      } finally {
        setTimeout(() => {
          this.isLoading = false; // Desactivar loading
        }, 500);
      }
    },
    getDatosUsuarioFiltro(event) {
      if (event.value && event.value.iduse) {
        this.usuariodos_id = event.value.iduse;
        this.usuarioSeleccionadodos = event.value.nombre;
        this.listarPersona(this.buscar, this.criterio);
      }
    },
    cambiarPagina(page, buscar, criterio) {
      this.pagination.current_page = page;
      this.listarPersona(buscar, criterio);
    },
    async registrarPersona(datos) {
      try {
        this.isLoading = true; // Activar loading
        await axios.post("/cliente/registrar", datos);
        this.cerrarModal();
        await this.listarPersona(this.buscar, this.criterio);
        Swal.fire({
          title: 'Éxito',
          text: 'Cliente registrado correctamente',
          icon: 'success',
          confirmButtonText: 'OK'
        });
      } catch (error) {
        this.toastError("No se pudo registrar el cliente");
      } finally {
        this.isLoading = false; // Desactivar loading
      }
    },
    async actualizarPersona(datos) {
      try {
        this.isLoading = true; // Activar loading
        await axios.put("/cliente/actualizar", datos);
        this.cerrarModal();
        await this.listarPersona(this.buscar, this.criterio);
        Swal.fire({
          title: 'ACTUALIZACIÓN EXITOSA',
          icon: 'success',
          confirmButtonText: 'OK'
        });
      } catch (error) {
        this.toastError("No se pudo actualizar el cliente");
      } finally {
        this.isLoading = false; // Desactivar loading
      }
    },
    cerrarModal() {
      this.modal = false;
      this.tituloModal = "";
      this.errorPersona = 0;
      this.activaredit = false;
      this.idusuario = "";
    },
    abrirModal(modelo, accion, data = []) {
      if (modelo === "persona") {
        this.modal = true;
        if (accion === "registrar") {
          this.tituloModal = "REGISTRAR CLIENTE";
          this.tipoAccion = 1;
          this.datosFormulario = {
            nombre: "",
            tipo_documento: "",
            num_documento: "",
            complemento: "",
            direccion: "",
            telefono: "",
            email: "",
          };
        } else if (accion === "actualizar") {
          this.tituloModal = "ACTUALIZAR CLIENTE";
          this.tipoAccion = 2;
          this.datosFormulario = { ...data, usuariodos_id: "" };
          this.activaredit = true;
          this.verUsuario(data);
        }
      }
    },
    cargarReporteExcel() {
      window.open("/cliente/listarReporteClienteExcel", "_blank");
    },
    getTipoDocumentoText(value) {
      const tipos = {
        "1": "CI",
        "2": "CEX",
        "4": "NIT",
        "3": "PAS",
      };
      return tipos[value] || "";
    },
    recuperarIdRol() {
      this.rolUsuario = window.userData.rol;
    },

    async selectUsuario(search, loading) {
      try {
        this.isLoading = true; // Activar loading
        loading(true);
        const url = "/cliente/selectUusarioVend?filtro=" + search;
        const response = await axios.get(url);
        this.arrayUsuarioV = response.data.clientes;
      } catch (error) {
        console.error("Error al seleccionar usuario:", error);
        this.$toast.add({
          severity: "error",
          summary: "Error",
          detail: "Error al cargar usuarios",
          life: 3000,
        });
      } finally {
        loading(false);
        setTimeout(() => {
          this.isLoading = false; // Desactivar loading
        }, 5000);
      }
    },
    getDatosUsuario(val1) {
      let me = this;
      me.loading = true;

      if (val1 && val1.ID_use) {
        me.idusuario = val1.ID_use;
        me.usuarioSeleccionado = val1.nombre;
      }
    },
    async verUsuario(data) {
      try {
        this.isLoading = true; // Activar loading
        this.idusuario = data.usuario;
        const url = "/cliente/usuario?idusuario=" + this.idusuario;
        const response = await axios.get(url);
        this.arrayDetalleUsuario = response.data.usuario;
        this.usuarioSeleccionado = this.arrayDetalleUsuario[0].nombre;
      } catch (error) {
        console.error("Error al ver usuario:", error);
        this.$toast.add({
          severity: "error",
          summary: "Error",
          detail: "Error al cargar detalles del usuario",
          life: 3000,
        });
      } finally {
        setTimeout(() => {
          this.isLoading = false; // Desactivar loading
        }, 500);
      }
    },

    async listarDatosuser() {
      try {
        const response = await axios.get("/user-info");
        const userData = response.data.user;
        this.usuariodos_id = userData.iduse;
        this.role_id = userData.idrol;

        if (this.role_id == 1) {
          this.usuariodos_id = "";
        }

        await this.listarPersona(this.buscar, this.criterio);
      } catch (error) {
        console.error("Error al obtener la información del usuario:", error);
        this.$toast.add({
          severity: "error",
          summary: "Error",
          detail: "Error al obtener información del usuario",
          life: 3000,
        });
      }
    },

    abrirModalImportar() {
      this.modalImportar = 1;
    },
    cerrarModalImportar() {
      this.modalImportar = 0;
      this.listarPersona(this.buscar, this.criterio);
    },
  },

  async mounted() {
    try {
      this.isLoading = true; // Activar loading
      await Promise.all([this.listarDatosuser(), this.recuperarIdRol()]);

      // Configurar responsividad
      this.handleResize();
      window.addEventListener("resize", this.handleResize);
    } catch (error) {
      console.error("Error en la carga inicial:", error);
      this.$toast.add({
        severity: "error",
        summary: "Error",
        detail: "Error al cargar los datos iniciales",
        life: 3000,
      });
    } finally {
      this.isLoading = false; // Desactivar loading
    }
  },
  beforeDestroy() {
    window.removeEventListener("resize", this.handleResize);
  },
};
</script>
<style scoped>
.input-container {
  position: relative;
  padding-bottom: 20px;
  /* Aumentado de 8px a 12px para dar espacio al error */
  margin-bottom: 8px;
  /* Agregado margen inferior pequeño */
}

.input-container .p-inputtext {
  width: 100%;
  margin-bottom: 0;
  /* Eliminar margen inferior si existe */
}

.error-message {
  position: absolute;
  bottom: 2px;
  /* Ajustado para tener más espacio arriba del input */
  left: 0;
  font-size: 0.75rem;
  /* Tamaño de fuente más pequeño */
  margin-top: 0;
  /* Eliminado margen superior */
}

/* Panel Styles */
.cliente-panel {
  margin-bottom: 1rem;
}

/* Panel Content Spacing */
>>>.p-panel .p-panel-content {
  padding: 1rem;
}

>>>.p-panel .p-panel-header {
  padding: 0.75rem 1rem;
  background: #f8fafc;
  border-bottom: 1px solid #e5e7eb;
}

>>>.p-panel .p-panel-header .p-panel-title {
  font-weight: 600;
}

/* Search Bar Styles */
.search-bar {
  flex-grow: 1;
  display: flex;
  align-items: center;
  justify-content: flex-start;
  min-width: 0;
  margin-right: 1rem;
}

.search-bar .p-inputgroup {
  width: 100%;
}

.search-bar .p-inputgroup-addon {
  background: #f8fafc;
  border-color: #d1d5db;
  color: #6b7280;
}

.search-bar .p-inputtext {
  border-left: none;
}

.search-bar .p-inputtext:focus {
  box-shadow: 0 0 0 0.2rem rgba(59, 130, 246, 0.5);
  border-color: #3b82f6;
}

.search-bar .p-inputgroup-addon+.p-inputtext:focus {
  border-left-color: #3b82f6;
}

/* Responsive Dialog Styles */
.responsive-dialog>>>.p-dialog {
  margin: 0.75rem;
  max-height: 90vh;
  overflow-y: auto;
}

.responsive-dialog>>>.p-dialog-content {
  overflow-x: auto;
  padding: 0.75rem 1rem;
  /* Reducido padding vertical */
}

.responsive-dialog>>>.p-dialog-header {
  padding: 0.75rem 1.5rem;
  /* Reducido padding vertical */
  font-size: 1.1rem;
}

.responsive-dialog>>>.p-dialog-footer {
  padding: 0.5rem 1.5rem;
  /* Reducido padding vertical */
  gap: 0.5rem;
  flex-wrap: wrap;
  justify-content: flex-end;
}

/* Toolbar Responsive - Mantener en una línea */
.toolbar-container {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 10px;
  gap: 0.75rem;
  flex-wrap: nowrap;
}

.toolbar {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 10px;
  flex-shrink: 0;
}


/* Formulario compacto - Reducir espacios entre campos */
.form-compact>>>.p-field {
  margin-bottom: 0.25rem !important;
  /* Reducido de 0.5rem a 0.25rem */
}

>>>.p-fluid .p-field {
  margin-bottom: 0.25rem;
  /* Reducido de 0.5rem a 0.25rem */
}

/* Reducir padding del contenedor del diálogo */
.responsive-dialog>>>.p-dialog-content {
  padding: 0.75rem 1rem !important;
  /* Reducido padding vertical */
}

/* Estilos para campos obligatorios */
.required-field {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  font-weight: 600;
  color: #2c3e50;
}

.required-icon {
  color: #e74c3c;
  font-size: 1rem;
  font-weight: bold;
  margin-right: 0.2rem;
}

/* Estilos para campos opcionales */
.optional-field {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  font-weight: 500;
  color: #6c757d;
}

.optional-icon {
  color: #17a2b8;
  font-size: 0.8rem;
}

.activo {
  color: green;
  font-weight: bold;
}

.status-badge {
  padding: 0.25em 0.5em;
  border-radius: 4px;
  color: white;
}

.status-badge.active {
  background-color: rgb(0, 225, 0);
}

.status-badge.inactive {
  background-color: red;
}

/* DataTable Responsive */
>>>.p-datatable {
  font-size: 0.9rem;
}

>>>.p-datatable .p-datatable-tbody>tr>td {
  padding: 0.5rem;
  word-break: break-word;
  text-align: left;
}

>>>.p-datatable .p-datatable-thead>tr>th {
  padding: 0.75rem 0.5rem;
  font-size: 0.85rem;
}

.p-dialog-mask {
  z-index: 9990 !important;
}

.p-dialog {
  z-index: 9990 !important;
}

/* Tablet Styles */
@media (max-width: 1024px) {
  .responsive-dialog>>>.p-dialog {
    margin: 0.5rem;
    max-height: 95vh;
  }

  >>>.p-datatable {
    font-size: 0.85rem;
  }
}

/* Mobile Styles */
@media (max-width: 768px) {
  .toolbar .p-button .p-button-label {
    display: none;
  }

  .responsive-dialog>>>.p-dialog {
    margin: 0.25rem;
    max-height: 98vh;
  }

  .responsive-dialog>>>.p-dialog-content {
    padding: 0.5rem 0.75rem;
    /* Más compacto en móviles */
  }

  .responsive-dialog>>>.p-dialog-header {
    padding: 0.5rem 1rem;
    /* Reducido padding vertical */
    font-size: 1rem;
  }

  .responsive-dialog>>>.p-dialog-footer {
    padding: 0.4rem 1rem;
    /* Reducido padding vertical */
    justify-content: flex-end;
  }

  .toolbar-container {
    gap: 0.5rem;
  }

  >>>.p-datatable {
    font-size: 0.8rem;
  }

  >>>.p-datatable .p-datatable-tbody>tr>td {
    padding: 0.4rem 0.3rem;
  }

  >>>.p-datatable .p-datatable-thead>tr>th {
    padding: 0.5rem 0.3rem;
    font-size: 0.75rem;
  }

  /* Ajustar botones en móviles */
  >>>.p-button-sm {
    font-size: 0.75rem !important;
    padding: 0.375rem 0.5rem !important;
    min-width: auto !important;
  }

  /* Ajustar botón "Nuevo" para que coincida con otros botones */
  .toolbar>>>.p-button-sm {
    font-size: 0.75rem !important;
    padding: 0.375rem 0.5rem !important;
  }

  /* Reducir altura del input buscador */
  .search-bar .p-inputtext-sm {
    padding: 0.35rem 0.5rem !important;
    font-size: 0.85rem !important;
  }

  /* Ajustar el addon del icono de búsqueda en móviles */
  .search-bar .p-inputgroup-addon {
    padding: 0.35rem 0.5rem !important;
    font-size: 0.85rem !important;
  }

  /* Ajustar iconos en móviles */
  .required-icon {
    font-size: 0.8rem;
  }

  .optional-icon {
    font-size: 0.6rem;
  }

  >>>.p-inputtext,
  >>>.p-dropdown,
  >>>.p-inputnumber-input {
    font-size: 0.9rem;
    padding: 0.5rem;
  }

  /* Reducir espacios entre campos en móviles */
  .input-container {
    padding-bottom: 20px;
    /* Aumentado para dar espacio al error en móviles */
    margin-bottom: 6px;
  }
}

/* Extra Small Mobile */
@media (max-width: 480px) {
  .toolbar .p-button .p-button-label {
    display: none;
  }

  .responsive-dialog>>>.p-dialog {
    margin: 0.1rem;
    max-height: 99vh;
  }

  .responsive-dialog>>>.p-dialog-content {
    padding: 0.4rem 0.5rem;
    /* Más compacto en móviles extra pequeños */
  }

  .responsive-dialog>>>.p-dialog-header {
    padding: 0.4rem 0.75rem;
    /* Reducido padding vertical */
    font-size: 0.95rem;
  }

  .responsive-dialog>>>.p-dialog-footer {
    padding: 0.3rem 0.75rem;
    /* Reducido padding vertical */
    justify-content: flex-end;
  }

  .responsive-dialog>>>.p-dialog-footer .p-button {
    width: auto;
    margin-bottom: 0.25rem;
  }

  /* Toolbar mantiene elementos en una línea */
  .toolbar-container {
    gap: 0.4rem;
    flex-wrap: nowrap;
  }

  .toolbar {
    flex-shrink: 0;
    min-width: auto;
  }

  .search-bar {
    flex: 1;
    min-width: 0;
  }

  /* Ajustar botones para que coincidan */
  .toolbar>>>.p-button-sm {
    font-size: 0.75rem !important;
    padding: 0.375rem 0.5rem !important;
  }

  /* Reducir más la altura del input buscador en móviles pequeños */
  .search-bar .p-inputtext-sm {
    padding: 0.3rem 0.5rem !important;
    font-size: 0.8rem !important;
  }

  /* Ajustar el addon del icono de búsqueda en móviles extra pequeños */
  .search-bar .p-inputgroup-addon {
    padding: 0.3rem 0.5rem !important;
    font-size: 0.8rem !important;
  }

  >>>.p-datatable {
    font-size: 0.75rem;
  }

  >>>.p-datatable .p-datatable-tbody>tr>td {
    padding: 0.3rem 0.2rem;
  }

  >>>.p-datatable .p-datatable-thead>tr>th {
    padding: 0.4rem 0.2rem;
    font-size: 0.7rem;
  }

  /* Iconos más pequeños en móviles extra pequeños */
  .required-icon {
    font-size: 0.7rem;
  }

  .optional-icon {
    font-size: 0.55rem;
  }

  >>>.p-inputtext,
  >>>.p-dropdown,
  >>>.p-inputnumber-input {
    font-size: 0.85rem;
    padding: 0.4rem;
  }

  >>>.p-tag {
    font-size: 0.7rem;
    padding: 0.2rem 0.4rem;
  }

  /* Espacios aún más compactos en móviles extra pequeños */
  .input-container {
    padding-bottom: 20px;
    /* Aumentado para dar espacio al error en móviles pequeños */
    margin-bottom: 4px;
  }
}

/* Paginator Responsive */
@media (max-width: 768px) {
  >>>.p-paginator {
    flex-wrap: wrap !important;
    justify-content: center;
    font-size: 0.85rem;
    padding: 0.5rem;
  }

  >>>.p-paginator .p-paginator-page,
  >>>.p-paginator .p-paginator-next,
  >>>.p-paginator .p-paginator-prev,
  >>>.p-paginator .p-paginator-first,
  >>>.p-paginator .p-paginator-last {
    min-width: 32px !important;
    height: 32px !important;
    font-size: 0.85rem !important;
    padding: 0 6px !important;
    margin: 2px !important;
  }
}

@media (max-width: 480px) {
  >>>.p-paginator {
    font-size: 0.8rem;
    padding: 0.4rem;
  }

  >>>.p-paginator .p-paginator-page,
  >>>.p-paginator .p-paginator-next,
  >>>.p-paginator .p-paginator-prev,
  >>>.p-paginator .p-paginator-first,
  >>>.p-paginator .p-paginator-last {
    min-width: 28px !important;
    height: 28px !important;
    font-size: 0.8rem !important;
    padding: 0 4px !important;
    margin: 1px !important;
  }
}

/* Action Buttons in DataTable */
>>>.p-datatable .p-button {
  margin-right: 0.25rem;
}

@media (max-width: 768px) {
  >>>.p-datatable .p-button {
    margin-right: 0.15rem;
    margin-bottom: 0.15rem;
  }
}

>>>.p-fileupload .p-button.p-fileupload-choose {
  background-color: #22c55e !important;
  border-color: #22c55e !important;
  color: #ffffff !important;
  transition: all 0.2s ease-in-out !important;
}

/* Efecto hover */
>>>.p-fileupload .p-button.p-fileupload-choose:enabled:hover {
  background-color: #16a34a !important;
  border-color: #16a34a !important;
}

/* Efecto focus */
>>>.p-fileupload .p-button.p-fileupload-choose:focus {
  box-shadow: 0 0 0 0.2rem rgba(34, 197, 94, 0.5) !important;
}

/* Efecto active (cuando se hace clic) */
>>>.p-fileupload .p-button.p-fileupload-choose:enabled:active {
  background-color: #15803d !important;
  border-color: #15803d !important;
}

/* Estilo cuando está deshabilitado */
>>>.p-fileupload .p-button.p-fileupload-choose:disabled {
  background-color: #22c55e !important;
  border-color: #22c55e !important;
  opacity: 0.6;
}

>>>.p-fileupload .p-fileupload-buttonbar .p-button.p-component:not(.p-fileupload-choose) {
  background: #ef4444 !important;
  border-color: #ef4444 !important;
  color: #ffffff !important;
  transition: all 0.2s ease-in-out !important;
}

/* Efecto hover */
>>>.p-fileupload .p-fileupload-buttonbar .p-button.p-component:not(.p-fileupload-choose):enabled:hover {
  background: #dc2626 !important;
  border-color: #dc2626 !important;
}

/* Efecto focus */
>>>.p-fileupload .p-fileupload-buttonbar .p-button.p-component:not(.p-fileupload-choose):focus {
  box-shadow: 0 0 0 0.2rem rgba(239, 68, 68, 0.5) !important;
}

/* Efecto active (cuando se hace clic) */
>>>.p-fileupload .p-fileupload-buttonbar .p-button.p-component:not(.p-fileupload-choose):enabled:active {
  background: #b91c1c !important;
  border-color: #b91c1c !important;
}

/* Estilo cuando está deshabilitado */
>>>.p-fileupload .p-fileupload-buttonbar .p-button.p-component:not(.p-fileupload-choose):disabled {
  background: #ef4444 !important;
  border-color: #ef4444 !important;
  opacity: 0.6;
}

>>>.p-fileupload .p-fileupload-files .p-button {
  background: #ef4444 !important;
  border-color: #ef4444 !important;
  color: #ffffff !important;
  transition: all 0.2s ease-in-out !important;
}

/* Efecto hover */
>>>.p-fileupload .p-fileupload-files .p-button:enabled:hover {
  background: #dc2626 !important;
  border-color: #dc2626 !important;
}

/* Efecto focus */
>>>.p-fileupload .p-fileupload-files .p-button:focus {
  box-shadow: 0 0 0 0.2rem rgba(239, 68, 68, 0.5) !important;
}

/* Efecto active (cuando se hace clic) */
>>>.p-fileupload .p-fileupload-files .p-button:enabled:active {
  background: #b91c1c !important;
  border-color: #b91c1c !important;
}

/* Estilo cuando está deshabilitado */
>>>.p-fileupload .p-fileupload-files .p-button:disabled {
  background: #ef4444 !important;
  border-color: #ef4444 !important;
  opacity: 0.6;
}

/* Asegurar que el icono dentro del botón también sea blanco */
>>>.p-fileupload .p-fileupload-files .p-button .p-button-icon {
  color: #ffffff !important;
}

>>>.p-fileupload-row>div:first-child {
  display: none !important;
}

>>>.p-dialog .p-dialog-content {
  padding: 0 1.5rem 1.5rem 1.5rem;
}

/* Estilos del loader */
.loading-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0, 0, 0, 0.5);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 9999;
}

.loading-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  background-color: rgba(0, 0, 0, 0.3);
  backdrop-filter: blur(5px);
  padding: 30px;
  border-radius: 15px;
}

.spinner {
  width: 80px;
  height: 80px;
  border: 4px solid rgba(255, 255, 255, 0.2);
  border-radius: 50%;
  border-top: 4px solid rgba(255, 255, 255, 0.9);
  animation: spin 1s linear infinite;
}

.loading-text {
  margin-top: 20px;
  color: rgba(255, 255, 255, 0.9);
  letter-spacing: 3px;
  font-size: 14px;
}

@keyframes spin {
  0% {
    transform: rotate(0deg);
  }

  100% {
    transform: rotate(360deg);
  }
}
</style>
