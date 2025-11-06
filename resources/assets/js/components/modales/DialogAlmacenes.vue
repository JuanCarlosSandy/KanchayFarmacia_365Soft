<template>
  <main class="main">
    <Dialog
      header="Almacenes"
      :visible.sync="modal1"
      :modal="true"
      :containerStyle="dialogContainerStyle"
      :closable="false"
      :closeOnEscape="false"
      class="responsive-dialog"
    >
      <div class="toolbar-container">
        <div class="toolbar">
          <Button
            label="Nuevo"
            icon="pi pi-plus"
            @click="abrirModal('almacenes', 'registrar')"
            class="p-button-secondary p-button-sm"
          />
        </div>
        <div class="search-bar">
          <span class="p-input-icon-left">
            <i class="pi pi-search" />
            <InputText
              type="text"
              v-model="buscar"
              placeholder="Texto a buscar"
              class="p-inputtext-sm"
              @keyup="buscarAlmacenes"
            />
          </span>
        </div>
      </div>
      <DataTable
        class="p-datatable-sm p-datatable-gridlines"
        :value="arrayAlmacen"
        :paginator="true"
        responsiveLayout="scroll"
        :rows="7"
      >
        <Column field="opciones" header="Opciones">
          <template #body="slotProps">
            <Button
              icon="pi pi-check"
              class="p-button-sm p-button-success custom-icon-size"
              style="padding: 0.3rem 0.4rem; font-size: 0.75rem; width: auto; min-width: unset;"
              @click="seleccionarAlmacen(slotProps.data)"
            />
            <Button
              icon="pi pi-pencil"
              class="p-button-warning p-button-sm"
              style="padding: 0.3rem 0.4rem; font-size: 0.75rem; width: auto; min-width: unset;"
              @click="abrirModal('almacenes', 'actualizar', slotProps.data)"
            />
          </template>
        </Column>
        <Column field="nombre_almacen" header="Nombre Almacen" />
      </DataTable>
      <template #footer>
        <Button
          label="Cerrar"
          icon="pi pi-times"
          class="p-button-danger p-button-sm"
          @click="closeDialog"
        />
      </template>
    </Dialog>

    <Dialog
      :header="tituloModal"
      :visible.sync="modal"
      :modal="true"
      :closable="false"
      :containerStyle="formDialogContainerStyle"
      :closeOnEscape="false"
      class="responsive-dialog form-dialog"
    >
      <form @submit.prevent="enviarFormulario">
        <div class="p-fluid p-formgrid p-grid form-compact">
          <div class="p-field p-col-12 p-md-6">
            <label for="nombreAlmacen" class="required-field">
              <span class="required-icon">*</span>
              Nombre del almacén
            </label>
            <InputText
              id="nombreAlmacen"
              placeholder="Ej. Almacén Principal"
              v-model="datosFormulario.nombre_almacen"
              :class="{ 'p-invalid': errores.nombre_almacen }"
              @input="validarCampo('nombre_almacen')"
              required
            />
            <small v-if="errores.nombre_almacen" class="p-error">
              <strong>{{ errores.nombre_almacen }}</strong>
            </small>
          </div>
          <div class="p-field p-col-12 p-md-6">
            <label for="ubicacion" class="required-field">
              <span class="required-icon">*</span>
              Ubicación
            </label>
            <InputText
              id="ubicacion"
              placeholder="Ej. Calle 123, Ciudad"
              v-model="datosFormulario.ubicacion"
              :class="{ 'p-invalid': errores.ubicacion }"
              @input="validarCampo('ubicacion')"
              required
            />
            <small v-if="errores.ubicacion" class="p-error">
              <strong>{{ errores.ubicacion }}</strong>
            </small>
          </div>
          <div class="p-field p-col-12 p-md-6">
            <label for="encargados" class="required-field">
              <span class="required-icon">*</span>
              Encargados
            </label>
            <AutoComplete
              :multiple="true"
              v-model="usuariosSeleccionados"
              :suggestions="arrayUsuario"
              :dropdown="true"
              @complete="selectUsuario($event)"
              @item-select="actualizarEncargados"
              @item-unselect="actualizarEncargados"
              field="nombre"
              :class="{ 'p-invalid': errores.encargado }"
              @input="validarCampo('encargado')"
              placeholder="Buscar Usuarios..."
              required
            />
            <small v-if="errores.encargado" class="p-error">
              <strong>{{ errores.encargado }}</strong>
            </small>
          </div>
          <div class="p-field p-col-12 p-md-6">
            <label for="telefono" class="required-field">
              <span class="required-icon">*</span>
              Teléfono
            </label>
            <InputText
              id="telefono"
              placeholder="Ej. 123456789"
              v-model="datosFormulario.telefono"
              :class="{ 'p-invalid': errores.telefono }"
              @input="validarCampo('telefono')"
              required
            />
            <small v-if="errores.telefono" class="p-error">
              <strong>{{ errores.telefono }}</strong>
            </small>
          </div>
          <div class="p-field p-col-12 p-md-6">
            <label for="sucursal" class="required-field">
              <span class="required-icon">*</span>
              Sucursal
            </label>
            <AutoComplete
              class="p-inputtext-sm"
              v-model="sucursalSeleccionado"
              :suggestions="arraySucursal"
              @complete="selectSucursal($event)"
              @item-select="getDatosSucursales"
              :dropdown="true"
              field="nombre"
              forceSelection
              :class="{ 'p-invalid': errores.sucursal }"
              placeholder="Buscar Sucursales..."
              required
            />
            <small v-if="errores.sucursal" class="p-error">
              <strong>{{ errores.sucursal }}</strong>
            </small>
          </div>
          <div class="p-field p-col-12 p-md-6">
            <label for="observaciones" class="optional-field">
              <i class="pi pi-info-circle optional-icon"></i>
              Observaciones
              <span class="p-tag p-tag-secondary">Opcional</span>
            </label>
            <Textarea
              id="observaciones"
              placeholder="Ej. Horario de funcionamiento, Capacidad de almacenamiento, etc."
              rows="3"
              v-model="datosFormulario.observaciones"
            />
          </div>
        </div>
      </form>
      <template #footer>
        <Button
          label="Cerrar"
          icon="pi pi-times"
          class="p-button-sm p-button-danger"
          @click="cerrarModal"
        />
        <Button
          v-if="tipoAccion == 1"
          label="Guardar"
          icon="pi pi-check"
          class="p-button-sm p-button-success"
          @click="enviarFormulario()"
        />
        <Button
          v-if="tipoAccion == 2"
          label="Actualizar"
          icon="pi pi-check"
          class="p-button-sm p-button-warning"
          @click="enviarFormulario()"
        />
      </template>
    </Dialog>
  </main>
</template>

<script>
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import Dialog from "primevue/dialog";
import Button from "primevue/button";
import InputText from "primevue/inputtext";
import InputNumber from "primevue/inputnumber";
import Dropdown from "primevue/dropdown";
import AutoComplete from "primevue/autocomplete";
import Textarea from "primevue/textarea";
import { esquemaAlmacen } from "../../constants/validations";
import Swal from "sweetalert2";

// Asegúrate de importar tu esquema de validación

export default {
  props: {
    visible: {
      type: Boolean,
      required: true,
    },
  },
  components: {
    Button,
    DataTable,
    Column,
    Dialog,
    InputText,
    Dropdown,
    InputNumber,
    AutoComplete,
    Textarea,
  },
  data() {
    return {
      datosFormulario: {
        nombre_almacen: "",
        ubicacion: "",
        encargado: -1,
        telefono: "",
        sucursal: -1,
        observaciones: "",
      },
      modal1: this.visible,
      errores: {},
      buscar: "",
      tipoAccion: 0,
      arraySucursal: [],
      modal: false,
      tituloModal: "",
      arrayUsuario: [],
      usuariosSeleccionados: [],
      sucursalSeleccionado: null,
      almacenSeleccionado: null,
      arrayAlmacen: [],
    };
  },
  computed: {
    dialogContainerStyle() {
      if (window.innerWidth <= 480) {
        return { width: '95vw', maxWidth: '95vw', margin: '0 auto' };
      } else if (window.innerWidth <= 768) {
        return { width: '90vw', maxWidth: '90vw', margin: '0 auto' };
      } else if (window.innerWidth <= 1024) {
        return { width: '80vw', maxWidth: '800px', margin: '0 auto' };
      } else {
        return { width: '700px', maxWidth: '90vw', margin: '0 auto' };
      }
    },
    formDialogContainerStyle() {
      if (window.innerWidth <= 480) {
        return { width: '95vw', maxWidth: '95vw', margin: '0 auto' };
      } else if (window.innerWidth <= 768) {
        return { width: '90vw', maxWidth: '90vw', margin: '0 auto' };
      } else if (window.innerWidth <= 1024) {
        return { width: '85vw', maxWidth: '900px', margin: '0 auto' };
      } else {
        return { width: '700px', maxWidth: '90vw', margin: '0 auto' };
      }
    }
  },
  methods: {
    closeDialog() {
      this.$emit("close");
    },
    buscarAlmacenes() {
      this.listarAlmacenes(1, this.buscar);
    },
    selectSucursal(event) {
      let me = this;

      if (!event.query.trim().length) {
        var url = "/sucursal/selectedSucursal/filter?filtro=";
        axios
          .get(url)
          .then(function(response) {
            var respuesta = response.data;
            me.arraySucursal = respuesta.sucursales;
            me.loading = false;
          })
          .catch(function(error) {
            console.log(error);
            me.loading = false;
          });
      } else {
        this.loading = true;

        var url =
          "/sucursal/selectedSucursal/filter?filtro=" + me.sucursalSeleccionado;
        axios
          .get(url)
          .then(function(response) {
            var respuesta = response.data;
            me.arraySucursal = respuesta.sucursales;
            me.loading = false;
          })
          .catch(function(error) {
            console.log(error);
            me.loading = false;
          });
      }
    },
    getDatosSucursales(val1) {
      console.log("Ejecucion de sucursales");
      if (this.tipoAccion == 2) {
        this.datosFormulario.sucursal =
          val1 && val1.id ? val1.id : this.datosFormulario.sucursal2;
        delete this.datosFormulario["sucursal2"];
      } else {
        this.datosFormulario.sucursal =
          val1 && val1.value.id ? val1.value.id : null;
      }
    },

    actualizarEncargados() {
      const val1 = this.usuariosSeleccionados;
      if (this.tipoAccion === 2) {
        this.datosFormulario.encargado =
          val1 && val1.length > 0
            ? val1.map((v) => v.id).join(",")
            : this.datosFormulario.encargado2;
        delete this.datosFormulario["encargado2"];
        console.log("form ", this.datosFormulario);
      } else {
        this.datosFormulario.encargado =
          val1 && val1.length > 0 ? val1.map((v) => v.id).join(",") : "";
        console.log("form ", this.datosFormulario);
      }
      console.log("val 1", val1);
    },
    getDatosUsuarios(event) {
      const val1 = event.value;
      if (this.tipoAccion === 2) {
        this.datosFormulario.encargado =
          val1 && val1.length > 0
            ? val1.map((v) => v.id).join(",")
            : this.datosFormulario.encargado2;
        delete this.datosFormulario["encargado2"];
      } else {
        this.datosFormulario.encargado =
          val1 && val1.length > 0 ? val1.map((v) => v.id).join(",") : "";
      }
      console.log("val 1", val1);
      console.log("datos formulario", this.datosFormulario);
    },
    selectUsuario(event) {
      let me = this;

      if (!event.query.trim().length) {
        var url = "/user/selectUser/filter?idrol=3";
        axios
          .get(url)
          .then(function(response) {
            var respuesta = response.data;
            me.arrayUsuario = respuesta.usuarios;
            me.loading = false;
          })
          .catch(function(error) {
            console.log(error);
            me.loading = false;
          });
      } else {
        this.loading = true;

        var url =
          "/user/selectUser/filter?filtro=" +
          event.query.toLowerCase() +
          "&idrol=3";
        axios
          .get(url)
          .then(function(response) {
            var respuesta = response.data;
            me.arrayUsuario = respuesta.usuarios;
            me.loading = false;
          })
          .catch(function(error) {
            console.log(error);
            me.loading = false;
          });
      }
    },
    seleccionarAlmacen(data) {
      this.almacenSeleccionado = data;
      this.$emit("almacen-seleccionado", this.almacenSeleccionado);
      this.$emit("close");
    },
    async validarCampo(campo) {
      try {
        console.log("formulario", this.datosFormulario);
        await esquemaAlmacen.validateAt(campo, this.datosFormulario);
        this.errores[campo] = null;
      } catch (error) {
        this.errores[campo] = error.message;
      }
    },
    async enviarFormulario() {
      await esquemaAlmacen
        .validate(this.datosFormulario, { abortEarly: false })
        .then(() => {
          // Verificar si el nombre del almacén está vacío
          if (!this.datosFormulario.nombre_almacen) {
            Swal.fire({
              icon: "error",
              title: "Campo vacío",
              text: "El nombre del almacén debe ser llenado.",
            });
            return;
          }
          if (this.tipoAccion == 2) {
            this.actualizarAlmacen(this.datosFormulario);
          } else {
            this.registrarAlmacen(this.datosFormulario);
          }
        })
        .catch((error) => {
          console.log(error);
          const erroresValidacion = {};
          error.inner.forEach((e) => {
            erroresValidacion[e.path] = e.message;
          });

          this.errores = erroresValidacion;
        });
    },
    listarAlmacenes(page, buscar, criterio) {
      let me = this;
      var url =
        "/almacennewview?page=" +
        page +
        "&buscar=" +
        buscar +
        "&criterio=" +
        criterio;
      axios
        .get(url)
        .then(function(response) {
          let respuesta = response.data;
          me.arrayAlmacen = respuesta.almacenes;
          me.pagination = respuesta.pagination;
          console.log("Array de almacenes:", me.arrayAlmacen); // Verifica los datos en arrayAlmacen
        })
        .catch(function(error) {
          console.log(error);
        });
    },

    registrarAlmacen(data) {
      console.log("sucursal ", this.sucursalSeleccionado);
      console.log("usuarios ", this.usuariosSeleccionados);
      console.log("DATA ", data);
      let me = this;
      axios
        .post("/almacen/registrar", data)
        .then(function(response) {
          me.cerrarModal();
          me.listarAlmacenes(1, "", "nombre_almacen");
          me.usuariosSeleccionados = [];
          me.arrayUsuario = [];
        })
        .catch(function(error) {
          console.log(error);
        });
    },
    actualizarAlmacen(data) {
      let me = this;
      axios
        .put("/almacen/editar", data)
        .then(function(response) {
          me.cerrarModal();
          //console.log(response)
          me.listarAlmacenes(1, "", "nombre_almacen");
        })
        .catch(function(error) {
          console.log(error);
        });
    },
    abrirModal(modelo, accion, data = []) {
      switch (modelo) {
        case "almacenes": {
          switch (accion) {
            case "registrar": {
              this.modal = true;
              this.modal1 = false;
              this.tituloModal = "Registrar Almacen";
              this.tipoAccion = 1;
              this.datosFormulario = {
                nombre_almacen: "",
                ubicacion: "",
                encargado: "",
                telefono: "",
                sucursal: "",
                observaciones: "",
              };
              this.errores = {};
              break;
            }
            case "actualizar": {
              console.log("Datos almacen:", data);
              this.modal = true;
              this.modal1 = false;
              this.tituloModal = "Actualizar Almacen";
              this.tipoAccion = 2;
              this.datosFormulario = {
                id: data["id"],
                nombre_almacen: data["nombre_almacen"],
                ubicacion: data["ubicacion"],
                encargado: data["encargado"],
                telefono: data["telefono"],
                sucursal: data["sucursal"],
                sucursal2: data["sucursal"],
                encargado2: data["encargado"],
                observaciones:
                  data["observacion"] == null ? "" : data["observacion"],
              };
              this.sucursalSeleccionado = data["nombre_sucursal"];
              this.usuarioSeleccionado = data["nombre_encargado"];

              this.errores = {};

              break;
            }
          }
        }
      }
    },
    cerrarModal() {
      this.modal = false;
      this.modal1 = true;
      this.tituloModal = "";
      this.errores = {};
      this.sucursalSeleccionado = "";
      this.usuarioSeleccionado = "";
      this.usuariosSeleccionados = "";
    },
  },
  mounted() {
    this.listarAlmacenes(1, "");
  },
};
</script>

<style scoped>
/* Arreglar icono de lupa - Centrado perfecto */
.search-bar .p-input-icon-left {
  position: relative;
  width: 100%;
}

.search-bar .p-input-icon-left i {
  position: absolute;
  left: 0.75rem;
  top: 0;
  bottom: 0;
  margin: auto 0;
  height: 1rem;
  z-index: 2;
  color: #6c757d;
  pointer-events: none;
  display: flex;
  align-items: center;
  line-height: 1;
}

.search-bar .p-input-icon-left .p-inputtext {
  padding-left: 2.5rem !important;
  width: 100%;
}

.form-group {
  margin-bottom: 15px;
}

/* Responsive Dialog Styles */
.responsive-dialog >>> .p-dialog {
  margin: 0.75rem;
  max-height: 90vh;
  overflow-y: auto;
}

.responsive-dialog >>> .p-dialog-content {
  overflow-x: auto;
  padding: 1rem;
}

.responsive-dialog >>> .p-dialog-header {
  padding: 1rem 1.5rem;
  font-size: 1.1rem;
}

.responsive-dialog >>> .p-dialog-footer {
  padding: 0.75rem 1.5rem;
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

.search-bar {
  flex-grow: 1;
  display: flex;
  align-items: center;
  justify-content: flex-start;
  min-width: 0;
}

.bold-input {
  font-weight: bold;
}

/* Formulario compacto - Reducir espacios entre campos */
.form-compact >>> .p-field {
  margin-bottom: 0.5rem !important;
}

>>> .p-fluid .p-field {
  margin-bottom: 0.5rem;
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

/* DataTable Responsive */
>>> .p-datatable {
  font-size: 0.9rem;
}

>>> .p-datatable .p-datatable-tbody > tr > td {
  padding: 0.5rem;
  word-break: break-word;
}

>>> .p-datatable .p-datatable-thead > tr > th {
  padding: 0.75rem 0.5rem;
  font-size: 0.85rem;
}

/* Form Grid Responsive */
>>> .p-formgrid.p-grid {
  margin: 0;
}

>>> .p-formgrid .p-field {
  padding: 0.5rem;
}

/* Tablet Styles */
@media (max-width: 1024px) {
  .responsive-dialog >>> .p-dialog {
    margin: 0.5rem;
    max-height: 95vh;
  }
  
  >>> .p-datatable {
    font-size: 0.85rem;
  }
  
  >>> .p-formgrid .p-field.p-col-12.p-md-6 {
    width: 100% !important;
    flex: 0 0 100% !important;
  }
}

/* Mobile Styles */
@media (max-width: 768px) {
  .responsive-dialog >>> .p-dialog {
    margin: 0.25rem;
    max-height: 98vh;
  }
  
  .responsive-dialog >>> .p-dialog-content {
    padding: 0.75rem;
  }
  
  .responsive-dialog >>> .p-dialog-header {
    padding: 0.75rem 1rem;
    font-size: 1rem;
  }
  
  .responsive-dialog >>> .p-dialog-footer {
    padding: 0.5rem 1rem;
    justify-content: flex-end;
  }
  
  .toolbar-container {
    gap: 0.5rem;
  }
  
  >>> .p-datatable {
    font-size: 0.8rem;
  }
  
  >>> .p-datatable .p-datatable-tbody > tr > td {
    padding: 0.4rem 0.3rem;
  }
  
  >>> .p-datatable .p-datatable-thead > tr > th {
    padding: 0.5rem 0.3rem;
    font-size: 0.75rem;
  }
  
  >>> .p-formgrid .p-field {
    padding: 0.25rem;
    margin-bottom: 0.4rem !important;
  }
  
  >>> .p-formgrid .p-field label {
    font-size: 0.9rem;
    margin-bottom: 0.25rem;
  }
  
  /* Ajustar iconos en móviles */
  .required-icon {
    font-size: 0.8rem;
  }
  
  .optional-icon {
    font-size: 0.6rem;
  }
  
  >>> .p-inputtext, >>> .p-dropdown, >>> .p-inputnumber-input, >>> .p-autocomplete-input {
    font-size: 0.9rem;
    padding: 0.5rem;
  }
  
  >>> .p-button-sm {
    font-size: 0.75rem !important;
    padding: 0.375rem 0.5rem !important;
    min-width: auto !important;
  }
  
  /* Ajustar botón "Nuevo" para que coincida con botón "Cerrar" */
  .toolbar >>> .p-button-sm {
    font-size: 0.75rem !important;
    padding: 0.375rem 0.5rem !important;
  }
  
  /* Reducir altura del input buscador */
  .search-bar .p-inputtext-sm {
    padding: 0.35rem 0.5rem 0.35rem 2.5rem !important;
    font-size: 0.85rem !important;
  }
}

/* Extra Small Mobile */
@media (max-width: 480px) {
  .responsive-dialog >>> .p-dialog {
    margin: 0.1rem;
    max-height: 99vh;
  }
  
  .responsive-dialog >>> .p-dialog-content {
    padding: 0.5rem;
  }
  
  .responsive-dialog >>> .p-dialog-header {
    padding: 0.5rem 0.75rem;
    font-size: 0.95rem;
  }
  
  /* Footer mantiene botones alineados a la derecha, no ocupan todo el ancho */
  .responsive-dialog >>> .p-dialog-footer {
    padding: 0.5rem 0.75rem;
    justify-content: flex-end;
  }
  
  .responsive-dialog >>> .p-dialog-footer .p-button {
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
  
  /* Ajustar botón "Nuevo" para que coincida con botón "Cerrar" */
  .toolbar >>> .p-button-sm {
    font-size: 0.75rem !important;
    padding: 0.375rem 0.5rem !important;
  }
  
  /* Reducir más la altura del input buscador en móviles pequeños */
  .search-bar .p-inputtext-sm {
    padding: 0.3rem 0.5rem 0.3rem 2.5rem !important;
    font-size: 0.8rem !important;
  }
  
  >>> .p-datatable {
    font-size: 0.75rem;
  }
  
  >>> .p-datatable .p-datatable-tbody > tr > td {
    padding: 0.3rem 0.2rem;
  }
  
  >>> .p-datatable .p-datatable-thead > tr > th {
    padding: 0.4rem 0.2rem;
    font-size: 0.7rem;
  }
  
  >>> .p-formgrid .p-field {
    padding: 0.2rem;
    margin-bottom: 0.3rem !important;
  }
  
  >>> .p-formgrid .p-field label {
    font-size: 0.85rem;
  }
  
  /* Iconos más pequeños en móviles extra pequeños */
  .required-icon {
    font-size: 0.7rem;
  }
  
  .optional-icon {
    font-size: 0.55rem;
  }
  
  >>> .p-inputtext, >>> .p-dropdown, >>> .p-inputnumber-input, >>> .p-autocomplete-input {
    font-size: 0.85rem;
    padding: 0.4rem;
  }
  
  >>> .p-tag {
    font-size: 0.7rem;
    padding: 0.2rem 0.4rem;
  }
}

/* Paginator Responsive */
@media (max-width: 768px) {
  >>> .p-paginator {
    flex-wrap: wrap !important;
    justify-content: center;
    font-size: 0.85rem;
    padding: 0.5rem;
  }
  
  >>> .p-paginator .p-paginator-page,
  >>> .p-paginator .p-paginator-next,
  >>> .p-paginator .p-paginator-prev,
  >>> .p-paginator .p-paginator-first,
  >>> .p-paginator .p-paginator-last {
    min-width: 32px !important;
    height: 32px !important;
    font-size: 0.85rem !important;
    padding: 0 6px !important;
    margin: 2px !important;
  }
}

@media (max-width: 480px) {
  >>> .p-paginator {
    font-size: 0.8rem;
    padding: 0.4rem;
  }
  
  >>> .p-paginator .p-paginator-page,
  >>> .p-paginator .p-paginator-next,
  >>> .p-paginator .p-paginator-prev,
  >>> .p-paginator .p-paginator-first,
  >>> .p-paginator .p-paginator-last {
    min-width: 28px !important;
    height: 28px !important;
    font-size: 0.8rem !important;
    padding: 0 4px !important;
    margin: 1px !important;
  }
}

/* Action Buttons in DataTable */
>>> .p-datatable .p-button {
  margin-right: 0.25rem;
}

@media (max-width: 768px) {
  >>> .p-datatable .p-button {
    margin-right: 0.15rem;
    margin-bottom: 0.15rem;
  }
}

/* Error Messages Responsive */
>>> .p-error {
  font-size: 0.8rem;
}

@media (max-width: 480px) {
  >>> .p-error {
    font-size: 0.75rem;
  }
}
</style>

<!-- Estilos globales necesarios para z-index -->
<style>
.p-dialog-mask {
  z-index: 9990 !important;
}
.p-dialog {
  z-index: 9990 !important;
}
.swal-zindex {
  z-index: 99999 !important;
}
</style>
