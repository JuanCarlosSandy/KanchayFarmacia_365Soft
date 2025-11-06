<template>
  <div class="main-container">
    <div class="loading-overlay" v-if="isLoading">
      <div class="loading-container">
        <div class="spinner"></div>
        <div class="loading-text">LOADING...</div>
      </div>
    </div>
    <Panel class="ingreso-panel">
      <template #header>
        <div class="panel-header header-flex">
          <div class="header-title-icon">
            <i class="pi pi-shopping-cart panel-icon"></i>
            <h4 class="panel-title">DETALLE COMPRA</h4>
          </div>
          <div class="linear-stepper compact-stepper">
            <div class="step-container">
              <div
                class="step"
                :class="{ active: step === 1, completed: step > 1 }"
              >
                <span class="step-number" v-if="step > 1">✔</span>
                <span class="step-number" v-else>1</span>
              </div>
              <div class="step-line" v-if="step >= 2"></div>
            </div>
            <div class="step-container">
              <div
                class="step"
                :class="{ active: step === 2, completed: step > 2 }"
              >
                <span class="step-number" v-if="step > 2">✔</span>
                <span class="step-number" v-else>2</span>
              </div>
            </div>
          </div>
        </div>
      </template>
      <div v-show="step === 1" class="step-content">
        <Panel header="Datos de Comprobante y Almacén">
          <div class="p-fluid p-formgrid p-grid">
            <div class="p-field p-col-12 p-md-4">
              <label for="tipoComprobante" class="font-weight-bold">
                Tipo comprobante
                <Tag severity="danger" class="obligatorio-rojo">OBLIG</Tag>
              </label>
              <Dropdown
                id="tipoComprobante"
                v-model="tipo_comprobante"
                :options="tipoComprobanteOptions"
                optionLabel="label"
                optionValue="value"
                placeholder="Seleccione"
                class="w-full"
              />
            </div>
            <div class="p-field p-col-12 p-md-4">
              <label for="numComprobante" class="font-weight-bold">
                N° Comprobante
                <Tag severity="danger" class="obligatorio-rojo">OBLIG</Tag>
              </label>
              <InputText
                id="numComprobante"
                v-model="num_comprobante"
                placeholder="000xx"
                ref="numeroComprobanteRef"
                class="w-full"
              />
              <small>Shift + E</small>
            </div>
            <div class="p-field p-col-12 p-md-4">
              <label for="almacen" class="font-weight-bold">
                Almacen Destino
                <Tag severity="danger" class="obligatorio-rojo">OBLIG</Tag>
              </label>
              <Dropdown
                id="almacen"
                v-model="AlmacenSeleccionado"
                :options="arrayAlmacenes"
                optionLabel="nombre_almacen"
                optionValue="id"
                placeholder="Seleccione"
                :disabled="idrolUsuario != 4"
              />
            </div>
          </div>
        </Panel>
      </div>
      <div v-show="step === 2" class="step-content">
        <Panel header="Detalle de Compra">
          <div class="almacen-busqueda-flex">
            <div class="buscador-container">
              <input
                type="text"
                v-model="buscarA"
                @input="listarArticuloDebounced(buscarA, criterioA)"
                class="form-control buscador-input"
                placeholder="Texto a buscar"
              />
              <button
                type="button"
                class="reset-buscar-btn"
                @click="resetBuscarA"
                title="Limpiar búsqueda"
              >
                <i class="pi pi-times"></i>
              </button>
            </div>
          </div>

          <div class="modal-body">
            <DataTable
              :value="arrayArticulo"
              responsiveLayout="scroll"
              stripedRows
              size="small"
              class="p-datatable-sm"
              paginator
              :rows="5"
            >
              <Column header="Opciones">
                <template #body="slotProps">
                  <Button
                    icon="pi pi-check"
                    class="p-button-success p-button-sm"
                    style="padding: 0.3rem 0.4rem; font-size: 0.75rem; width: auto; min-width: unset;"
                    @click="agregarDetalleModal(slotProps.data)"
                  />
                </template>
              </Column>

              <Column field="codigo" header="Código" />
              <Column field="nombre" header="Nombre comercial" />
              <Column field="nombre_proveedor" header="Proveedor" />

              <Column field="precio_costo_unid" header="Costo unit" />
              <Column field="precio_costo_paq" header="Costo Paquete" />
            </DataTable>
          </div>
          <div class="p-col-12">
            <DataTable
              :value="arrayDetalle"
              responsiveLayout="scroll"
              class="p-datatable-sm"
            >
              <Column header="Acciones" style="width: 100px">
                <template #body="slotProps">
                  <Button
                    icon="pi pi-trash"
                    class="p-button-danger p-button-sm"
                    style="padding: 0.3rem 0.4rem; font-size: 0.75rem; width: auto; min-width: unset;"
                    @click="eliminarDetalle(slotProps.index)"
                  />
                </template>
              </Column>
              <Column field="codigo" header="Codigo" />
              <Column field="articulo" header="Producto" />
              <Column header="Costo unitario">
                <template #body="slotProps">
                  <InputNumber
                    :value="slotProps.data.precio"
                    :min="0"
                    :step="0.01"
                    locale="es-ES"
                    :minFractionDigits="2"
                    :maxFractionDigits="2"
                    class="w-full"
                    @input="onPrecioUnitarioInput($event, slotProps.data)"
                  />
                </template>
              </Column>
              <Column header="Costo paquete">
                <template #body="slotProps">
                  <InputNumber
                    :value="slotProps.data.precio_paquete"
                    :min="0"
                    :step="0.01"
                    locale="es-ES"
                    :minFractionDigits="2"
                    :maxFractionDigits="2"
                    class="w-full"
                    @input="onPrecioPaqueteInput($event, slotProps.data)"
                  />
                </template>
              </Column>
              <Column field="unidad_x_paquete" header="Unid x Paq" />
              <Column header="Fecha vencimiento">
                <template #body="slotProps">
                  <InputText
                    type="date"
                    v-model="slotProps.data.fecha_vencimiento"
                    class="w-full"
                  />
                </template>
              </Column>
              <Column header="Unidades">
                <template #body="slotProps">
                  <InputNumber
                    v-model="slotProps.data.cantidad"
                    class="w-full"
                  />
                </template>
              </Column>
              <Column header="Subtotal">
                <template #body="slotProps">
                  {{
                    (
                      slotProps.data.precio *
                      slotProps.data.cantidad *
                      parseFloat(monedaCompra[0])
                    ).toFixed(2)
                  }}
                  {{ monedaCompra[1] }}
                </template>
              </Column>
            </DataTable>
            <div class="p-d-flex p-jc-end p-mt-2">
              <b>Total Neto:</b>
              <span class="p-ml-2"
                >{{ (calcularTotal * parseFloat(monedaCompra[0])).toFixed(2) }}
                {{ monedaCompra[1] }}</span
              >
            </div>
          </div>
          <div class="p-d-flex p-jc-end p-mt-3 compra-action-buttons-row">
            <Button
              label="Cerrar"
              class="p-button-danger p-button-sm compra-btn-custom"
              @click="cerrarFormulario()"
            />
            <Button
              label="Registrar Compra"
              class="p-button-success p-button-sm compra-btn-custom"
              @click="confirmarRegistroCompra"
            />
          </div>
        </Panel>
      </div>
      <div class="buttons p-d-flex p-jc-center p-mt-4 step-buttons-row">
        <Button
          label="Anterior"
          class="p-button-secondary p-button-sm step-btn-custom"
          @click="prevStep"
          :disabled="step === 1"
        />
        <Button
          label="Siguiente"
          class="p-button-primary p-button-sm step-btn-custom"
          @click="validarYAvanzar"
          :disabled="step === 2"
        />
      </div>
    </Panel>
  </div>
</template>

<script>
import Dropdown from "primevue/dropdown";
import InputText from "primevue/inputtext";
import InputNumber from "primevue/inputnumber";
import Button from "primevue/button";
import Tag from "primevue/tag";
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import Panel from "primevue/panel";
import Swal from "sweetalert2";
import debounce from "lodash/debounce";

export default {
  components: {
    Dropdown,
    InputText,
    InputNumber,
    Button,
    Tag,
    DataTable,
    Column,
    Panel,
    Swal,
  },
  props: {
    monedaPrincipal: {
      type: Array,
      required: true,
    },
    arrayArticuloSeleccionado: {
      type: Object,
      required: false,
    },
    arrayPedidoSeleccionado: {
      type: Object,
      required: false,
    },
    arrayDetallePedido: {
      type: Array,
      required: false,
    },
    monedaCompra: {
      type: Array,
      required: true,
    },
  },
  created() {
    this.selectAlmacen();
    this.articuloSeleccionadoLocal = { ...this.arrayArticuloSeleccionado };
    if (this.arrayDetallePedido) {
      this.arrayDetalle = [...this.arrayDetallePedido];

      this.AlmacenSeleccionado = this.arrayPedidoSeleccionado.idalmacen;
      this.proveedorSeleccionado = this.arrayPedidoSeleccionado.nombre_proveedor;
      this.idproveedor = this.arrayPedidoSeleccionado.idproveedor;
    }
  },
  data() {
    return {
      tipoComprobanteOptions: [
        { label: "Seleccione", value: "0" },
        { label: "Boleta", value: "BOLETA" },
        { label: "Factura", value: "FACTURA" },
        { label: "Ticket", value: "TICKET" },
      ],
      editarPrecioOptions: [
        { label: "Costo unitario", value: "1" },
        { label: "Costo paquete", value: "0" },
      ],
      isLoading: false,
      editarPrecios: "1",
      fechavencimiento: "",

      nuevoPrecio: 0,
      nuevoCostoUnidad: 0,
      nuevoCostoPaquete: 0,
      precios: [],
      precio_uno: 0,
      precio_dos: 0,
      precio_tres: 0,
      precio_cuatro: 0,
      step: 1,
      proveedorSeleccionado: "",
      tipoUnidadSeleccionada: "Unidades",
      arrayArticuloSeleccionadoLocal: {},
      AlmacenSeleccionado: null,
      idrolUsuario: null,
      arrayAlmacenes: [],
      idproveedor: 0,
      tipo_comprobante: "BOLETA",
      num_comprobante: "",
      impuesto: 0.18,
      total: 0.0,
      arrayDetalle: [],
      listado: 1,
      modal: 0,
      errorIngreso: 0,
      errorMostrarMsjIngreso: [],
      criterio: "num_comprobante",
      buscar: "",
      criterioA: "nombre",
      buscarA: "",
      arrayArticulo: [],
      idarticulo: 0,
      codigo: "",
      articulo: "",
      precio: 0,
      cantidad: 1,
    };
  },
  watch: {
    codigo(newValue) {
      if (newValue) {
        this.buscarArticulo();
      }
    },
    AlmacenSeleccionado(newVal) {
      // Cuando cambia el almacén, actualizar todos los detalles existentes
      if (newVal && this.arrayDetalle && this.arrayDetalle.length > 0) {
        this.arrayDetalle.forEach((detalle) => {
          detalle.idalmacen = newVal;
        });
      }
    },
    arrayDetalle: {
      deep: true,
      handler: function(newVal) {
        if (Array.isArray(newVal)) {
          this.total = newVal.reduce((acc, detalle) => {
            return acc + detalle.cantidad * detalle.precio;
          }, 0);
        } else {
          console.error("arrayDetalle no es un array:", newVal);
        }
      },
    },
  },
  computed: {
    fechaPorDefecto() {
      const today = new Date();
      const year = today.getFullYear();
      const month = String(today.getMonth() + 1).padStart(2, "0");
      const day = String(today.getDate()).padStart(2, "0");
      this.fechavencimiento = `${year}-${month}-${day}`;

      return this.fechavencimiento;
    },
    calcularTotal: function() {
      var resultado = 0.0;
      for (var i = 0; i < this.arrayDetalle.length; i++) {
        resultado =
          resultado +
          this.arrayDetalle[i].precio * this.arrayDetalle[i].cantidad;
      }
      return resultado;
    },
  },
  async mounted() {
    try {
      this.isLoading = true; // Activar loading
      await Promise.all([
        this.listarPrecio(),
        this.listarArticulo("", ""),
      ]);
      window.addEventListener("keydown", this.handleKeyPress);
    } catch (error) {
      console.error("Error en la carga inicial:", error);
      swal("Error", "Error al cargar datos iniciales", "error");
    } finally {
      setTimeout(() => {
        this.isLoading = false; // Desactivar loading
      }, 500);
    }
  },
  beforeUnmount() {
    window.removeEventListener("keydown", this.handleKeyPress);
  },
  methods: {
    async confirmarRegistroCompra() {
      const result = await Swal.fire({
        title: '¿Seguro que quiere registrar la compra?',
        text: '¿Verificó bien los datos?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Registrar',
        cancelButtonText: 'Volver',
        customClass: {
          confirmButton: 'custom-swal-confirm',
          cancelButton: 'custom-swal-cancel'
        },
        reverseButtons: true
      });
      if (result.isConfirmed) {
        this.registrarIngreso();
      }
    },
    resetBuscarA() {
      this.buscarA = "";
      this.listarArticuloDebounced("", this.criterioA);
    },

    async onPrecioUnitarioInput(valor, item) {
      // Actualiza precio unitario y recalcula precio paquete
      item.precio = valor;
      if (item.unidad_x_paquete && !isNaN(item.unidad_x_paquete)) {
        item.precio_paquete =
          parseFloat(valor) * parseFloat(item.unidad_x_paquete);
      }
      // Llama a la función para actualizar en backend
      await this.cambiarPrecios(
        item.precio,
        item.precio_paquete,
        "Costo unitario",
        item.idarticulo
      );
      // Refresca la tabla de productos
      this.listarArticulo(this.buscarA, this.criterioA);
    },

    async onPrecioPaqueteInput(valor, item) {
      // Actualiza precio paquete y recalcula precio unitario
      item.precio_paquete = valor;
      if (
        item.unidad_x_paquete &&
        !isNaN(item.unidad_x_paquete) &&
        parseFloat(item.unidad_x_paquete) !== 0
      ) {
        item.precio = parseFloat(valor) / parseFloat(item.unidad_x_paquete);
      }
      // Llama a la función para actualizar en backend
      await this.cambiarPrecios(
        item.precio,
        item.precio_paquete,
        "Costo paquete",
        item.idarticulo
      );
      // Refresca la tabla de productos
      this.listarArticulo(this.buscarA, this.criterioA);
    },

    listarArticulo(buscar, criterio) {
      let me = this;
      var url =
        "/articulo/listarArticulo?buscar=" + buscar + "&criterio=" + criterio;
      axios
        .get(url)
        .then(function(response) {
          var respuesta = response.data;
          me.arrayArticulo = respuesta.articulos;
        })
        .catch(function(error) {});
    },
    listarArticuloDebounced: debounce(function(buscar, criterio) {
      this.listarArticulo(buscar, criterio);
    }, 200), // espera 500ms

    handleKeyPress(event) {
      if (event.shiftKey && event.key === "T") {
        this.editarPrecio();
      }
    },

    validarYAvanzar() {
      const errores = [];
      if (this.step === 1) {
        if (this.tipo_comprobante === "0")
          errores.push("Seleccione un tipo de comprobante");
      } else if (this.step === 2) {
        if (this.AlmacenSeleccionado === "0" || this.AlmacenSeleccionado === 0)
          errores.push("Seleccione un almacén");
        if (!this.arrayArticuloSeleccionado.id)
          errores.push("Seleccione un producto");
        if (this.cantidad === 0) errores.push("Ingrese una cantidad válida");
      }
      if (errores.length > 0) {
        const mensaje = errores.join("\n");
        swal("Campos incompletos", mensaje, "warning");
      } else {
        this.nextStep();
      }
    },

    nextStep() {
      if (this.step < 3) {
        this.step++;
      }
    },

    prevStep() {
      if (this.step > 1) {
        this.step--;
      }
    },

    async buscarArticulo() {
      if (!this.AlmacenSeleccionado || this.AlmacenSeleccionado === 0) {
        Swal.fire({
          icon: "warning",
          title: "Almacén no seleccionado",
          text: "Debe seleccionar un almacén antes de buscar productos.",
        });
        return;
      }
      try {
        if (this.searchTimeout) {
          clearTimeout(this.searchTimeout);
        }
        this.searchTimeout = setTimeout(async () => {
          this.isLoading = true; // Activar loading
          let me = this;
          var url =
            "/articulo/listarArticulo?buscar=" +
            me.codigo +
            "&criterio=codigo&idProveedor=" +
            this.idproveedor;
          try {
            const response = await axios.get(url);
            let respuesta = response.data;
            me.arrayArticuloSeleccionado = respuesta.articulos.data[0];
          } catch (error) {
            swal("Error", "No se pudo buscar el artículo", "error");
          } finally {
            setTimeout(() => {
              this.isLoading = false; // Desactivar loading
            }, 500);
          }
        }, 1000);
      } catch (error) {
        console.error(error);
        this.isLoading = false;
      }
    },

    eliminarDetalle(index) {
      let me = this;
      me.arrayDetalle.splice(index, 1);
    },

    cerrarFormulario() {
      this.$emit("cerrar");
    },

    editarEstado() {
      const datos = {
        pedido: this.arrayPedidoSeleccionado,
        detalles: this.arrayDetallePedido,
      };
      this.$emit("editarEstadoPedido", datos);
    },

    listarIngreso(page, buscar, criterio) {
      const datos = {
        page: page,
        buscar: buscar,
        criterio: criterio,
      };
      this.$emit("listarIngreso", datos);
    },

    encuentra(id) {
      var sw = 0;
      for (var i = 0; i < this.arrayDetalle.length; i++) {
        if (this.arrayDetalle[i].idarticulo == id) {
          sw = true;
        }
      }
      return sw;
    },

    selectAlmacen() {
      axios
        .get("/almacen/almaceneslista")
        .then((response) => {
          this.arrayAlmacenes = response.data.almacenes;
          this.idrolUsuario = response.data.idrol;
          if (this.arrayAlmacenes.length === 1) {
            this.AlmacenSeleccionado = this.arrayAlmacenes[0].id;
          }
        })
        .catch((error) => {
          console.error(error);
        });
    },

    async editarPrecio() {
      try {
        this.isLoading = true; // Activar loading
        let me = this;
        if (me.editarPrecios == "1") {
          me.nuevoCostoPaquete = (
            me.nuevoPrecio * me.arrayArticuloSeleccionado.unidad_envase
          ).toFixed(2);
          me.arrayArticuloSeleccionado.precio_costo_paq = me.nuevoCostoPaquete;
          me.arrayArticuloSeleccionado.precio_costo_unid = me.nuevoPrecio;
          await this.cambiarPrecios(
            me.arrayArticuloSeleccionado.precio_costo_unid,
            me.nuevoCostoPaquete,
            "Costo unitario"
          );
        }
        if (me.editarPrecios == "0") {
          me.nuevoCostoUnidad = (
            me.nuevoPrecio / me.arrayArticuloSeleccionado.unidad_envase
          ).toFixed(2);
          me.arrayArticuloSeleccionado.precio_costo_unid = me.nuevoCostoUnidad;
          me.arrayArticuloSeleccionado.precio_costo_paq = me.nuevoPrecio;
          await this.cambiarPrecios(
            me.nuevoCostoUnidad,
            me.arrayArticuloSeleccionado.precio_costo_paq,
            "Costo paquete"
          );
        }
      } catch (error) {
        console.error("Error al editar precio:", error);
        swal("Error", "No se pudo actualizar el precio", "error");
      } finally {
        setTimeout(() => {
          this.isLoading = false; // Desactivar loading
        }, 500);
      }
    },

    calcularPrecio(precio, index, preciounidad) {
      const margen_ganancia =
        parseFloat(preciounidad) * (parseFloat(precio.porcentage) / 100);
      const precio_publico = parseFloat(preciounidad) + margen_ganancia;

      if (index === 0) {
        this.precio_uno = precio_publico.toFixed(2);
      } else if (index === 1) {
        this.precio_dos = precio_publico.toFixed(2);
      } else if (index === 2) {
        this.precio_tres = precio_publico.toFixed(2);
      } else if (index === 3) {
        this.precio_cuatro = precio_publico.toFixed(2);
      }
    },

    async cambiarPrecios(
      precioUnidad,
      precioPaquete,
      editarPrecios,
      idArticulo = null
    ) {
      let me = this;
      // Si se pasa idArticulo, usarlo, si no, usar el seleccionado
      const articuloId =
        idArticulo ||
        (me.arrayArticuloSeleccionado && me.arrayArticuloSeleccionado.id);
      me.precios.forEach((precio, index) => {
        me.calcularPrecio(precio, index, precioUnidad);
      });
      // No mostrar swal de confirmación para edición directa en tabla
      try {
        await axios.post("/articulo/actualizarPrecios", {
          id: articuloId,
          precio_costo_paquete: precioPaquete,
          precio_costo_unidad: precioUnidad,
          precio_uno: me.precio_uno,
          precio_dos: me.precio_dos,
          precio_tres: me.precio_tres,
          precio_cuatro: me.precio_cuatro,
        });
      } catch (error) {
        console.error(error);
      }
    },

    listarPrecio() {
      let me = this;
      var url = "/precios";
      axios
        .get(url)
        .then(function(response) {
          var respuesta = response.data;
          me.precios = respuesta.precio.data;
        })
        .catch(function(error) {});
    },

    async registrarIngreso() {
      if (this.validarIngreso()) {
        swal("Error", this.errorMostrarMsjIngreso.join("\n"), "error");
        return;
      }
      if (this.arrayDetalle.length === 0) {
        swal(
          "Error",
          "No se ha seleccionado ningún producto. No se puede realizar la compra.",
          "error"
        );
        return;
      }
      try {
        this.isLoading = true; // Activar loading
        let me = this;
        const response = await axios.post("/ingreso/registrar", {
          idproveedor: this.idproveedor,
          idalmacen: this.AlmacenSeleccionado,
          tipo_comprobante: this.tipo_comprobante,
          num_comprobante: this.num_comprobante,
          impuesto: this.impuesto,
          total: this.total,
          data: this.arrayDetalle,
        });
        if (response.data && response.data.error) {
          swal("Error", response.data.error, "error");
          return;
        }
        const inventarioResult = await this.guardarInventarios();
        if (inventarioResult.success) {
          me.listado = 1;
          await this.listarIngreso(1, "", "num_comprobante");
          swal({
            type: "success",
            title: "Éxito!",
            text: "Compra registrada correctamente",
          });
          this.$emit("cerrar");
        } else {
          swal("Error", inventarioResult.message, "error");
        }
      } catch (error) {
        console.error(error);
        let mensaje = "No se pudo registrar la compra";
        if (
          error.response &&
          error.response.data &&
          error.response.data.message
        ) {
          mensaje = error.response.data.message;
        } else if (error.message) {
          mensaje = error.message;
        }
        swal("Error", mensaje, "error");
      } finally {
        this.isLoading = false;
      }
    },

    async guardarInventarios() {
      try {
        this.editarEstado();
        const response = await axios.post("/inventarios/registrar", {
          inventarios: this.arrayDetalle,
        });
        if (response.data && response.data.error) {
          return { success: false, message: response.data.error };
        }
        return { success: true };
      } catch (error) {
        let mensaje = "No se pudo registrar el inventario";
        if (
          error.response &&
          error.response.data &&
          error.response.data.message
        ) {
          mensaje = error.response.data.message;
        } else if (error.message) {
          mensaje = error.message;
        }
        return { success: false, message: mensaje };
      }
    },

    validarIngreso() {
      this.errorIngreso = 0;
      this.errorMostrarMsjIngreso = [];
      if (
        !this.AlmacenSeleccionado ||
        this.AlmacenSeleccionado === 0 ||
        this.AlmacenSeleccionado === "0"
      ) {
        this.errorMostrarMsjIngreso.push("Seleccione un almacén");
      }
      if (this.tipo_comprobante === "0" || !this.tipo_comprobante) {
        this.errorMostrarMsjIngreso.push("Seleccione el tipo de comprobante");
      }
      if (!this.num_comprobante || this.num_comprobante.trim() === "") {
        this.errorMostrarMsjIngreso.push("Ingrese el número de comprobante");
      }
      if (!this.impuesto) {
        this.errorMostrarMsjIngreso.push("Ingrese el impuesto de compra");
      }
      if (this.arrayDetalle.length <= 0) {
        this.errorMostrarMsjIngreso.push(
          "Agregue al menos un detalle de producto"
        );
      }
      if (this.errorMostrarMsjIngreso.length) this.errorIngreso = 1;
      return this.errorIngreso;
    },

    agregarDetalle() {
      let me = this;
      if (
        me.arrayArticuloSeleccionado.length == 0 ||
        me.cantidad == 0 ||
        me.AlmacenSeleccionado == 0
      ) {
      } else if (me.fechavencimiento == null) {
        swal({
          type: "error",
          title: "Error...",
          text: "No se ingresó fecha de vencimiento!",
        });
      } else {
        if (me.encuentra(me.arrayArticuloSeleccionado.id)) {
          swal({
            type: "error",
            title: "Error...",
            text: "Este Artículo ya se encuentra agregado!",
          });
        } else {
          if (me.tipoUnidadSeleccionada == "Paquetes") {
            me.arrayDetalle.push({
              idarticulo: me.arrayArticuloSeleccionado.id,
              idalmacen: me.AlmacenSeleccionado,
              codigo: me.arrayArticuloSeleccionado.codigo,
              articulo: me.arrayArticuloSeleccionado.nombre,
              precio: me.arrayArticuloSeleccionado.precio_costo_unid,
              precio_paquete: me.arrayArticuloSeleccionado.precio_costo_paq,
              unidad_x_paquete: me.arrayArticuloSeleccionado.unidad_envase,
              fecha_vencimiento: me.fechavencimiento,
              cantidad:
                me.cantidad * me.arrayArticuloSeleccionado.unidad_envase,
            });
          } else {
            me.arrayDetalle.push({
              idarticulo: me.arrayArticuloSeleccionado.id,
              idalmacen: me.AlmacenSeleccionado,
              codigo: me.arrayArticuloSeleccionado.codigo,
              articulo: me.arrayArticuloSeleccionado.nombre,
              precio: me.arrayArticuloSeleccionado.precio_costo_unid,
              precio_paquete: me.arrayArticuloSeleccionado.precio_costo_paq,
              fecha_vencimiento: me.fechavencimiento,
              unidad_x_paquete: me.arrayArticuloSeleccionado.unidad_envase,
              cantidad: me.cantidad,
            });
          }
          swal({
            type: "success",
            title: "Éxito!",
            text: "Artículo agregado correctamente!",
          });
          me.arrayArticuloSeleccionadoLocal = {};
          me.codigo = "";
          me.idarticulo = 0;
          me.articulo = "";
          me.cantidad = 1;
          me.fechavencimiento = null;
          me.precio = 0;
        }
      }
    },

    agregarDetalleModal(producto) {
      let me = this;
      // Evitar duplicados
      if (me.encuentra(producto.id)) {
        swal({
          type: "error",
          title: "Error...",
          text: "Este Artículo ya se encuentra agregado!",
        });
        return;
      }
      me.arrayDetalle.push({
        idarticulo: producto.id,
        idalmacen: me.AlmacenSeleccionado,
        codigo: producto.codigo,
        articulo: producto.nombre,
        precio: producto.precio_costo_unid,
        precio_paquete: producto.precio_costo_paq,
        unidad_x_paquete: producto.unidad_envase,
        fecha_vencimiento: me.fechaPorDefecto,
        cantidad: 1,
      });
    },
  },
};
</script>

<style scoped>
.step-content {
  margin-top: 0.2rem;
  padding-top: 0;
}
>>> .p-panel .p-panel-content {
  padding-top: 0.5rem !important;
}

.linear-stepper {
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 2px 0;
  position: relative;
}

.step-container {
  display: flex;
  align-items: center;
}

.step {
  display: flex;
  flex-direction: column;
  align-items: center;
  margin: 0 10px;
  opacity: 0.5;
  position: relative;
}

.step.active,
.step.completed {
  opacity: 1;
}

.step-number {
  width: 30px;
  height: 30px;
  border-radius: 50%;
  background-color: #ccc;
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: bold;
  font-size: 18px;
  z-index: 1;
}

.step.active .step-number {
  background-color: #007bff;
}

.step.completed .step-number {
  background-color: #34bc9b;
}

.step-line {
  height: 3px;
  width: 40px;
  background-color: #ccc;
  transition: background-color 0.3s;
  z-index: 0;
}

.step.completed + .step-line {
  background-color: #34bc9b;
}

.step.active + .step-line {
  background-color: #007bff;
}

.step-name {
  margin-top: 10px;
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
.obligatorio-rojo {
  background-color: #c53e3e;
  font-size: 0.6em;
}

/* Panel Content Spacing */
>>> .p-panel .p-panel-content {
  padding: 1rem;
}

>>> .p-panel .p-panel-header {
  padding: 0.75rem 1rem;
  background: #f8fafc;
  border-bottom: 1px solid #e5e7eb;
}

>>> .p-panel .p-panel-header .p-panel-title {
  font-weight: 600;
}
.header-flex {
  display: flex;
  align-items: center;
  justify-content: space-between;
}
.header-title-icon {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}
.compact-stepper.linear-stepper {
  margin: 0;
  min-width: 120px;
  max-width: 180px;
  justify-content: flex-end;
}
.compact-stepper .step {
  margin: 0 2px;
}
.compact-stepper .step-number {
  width: 20px;
  height: 20px;
  font-size: 12px;
}
.compact-stepper .step-name {
  font-size: 0.7em;
  margin-top: 2px;
}
.compact-stepper .step-line {
  width: 20px;
  height: 2px;
}
.almacen-busqueda-flex {
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
  gap: 1rem;
  margin-bottom: 0.5rem;
  flex-wrap: wrap;
}
.almacen-select-container {
  min-width: 220px;
  max-width: 350px;
  flex: 1 1 220px;
}
.almacen-dropdown {
  min-width: 180px;
  max-width: 320px;
  width: 100%;
}
.buscador-container {
  flex: 0 0 auto;
  display: flex;
  align-items: flex-end;
  justify-content: flex-start;
  margin-left: 0;
  margin-bottom: 0; /* Eliminar espacio extra abajo */
}
.buscador-input {
  width: 100%;
  max-width: 500px;
  min-width: 250px;
  padding: 0.35rem 0.75rem;
  font-size: 1rem;
  border-radius: 6px;
  box-sizing: border-box;
  margin-bottom: 0 !important; /* Eliminar margen inferior */
}
/* Eliminar margen superior de la tabla si existe */
.modal-body {
  margin-top: 0 !important;
  padding-top: 0 !important;
}
@media (max-width: 900px) {
  .almacen-busqueda-flex {
    flex-direction: column;
    align-items: stretch;
    gap: 0 !important;
  }
  .almacen-select-container {
    min-width: 0 !important;
    max-width: 100% !important;
    width: 100% !important;
    margin-bottom: 0 !important;
    padding-bottom: 0 !important;
  }
  .almacen-select-container label,
  .almacen-select-container .p-dropdown {
    margin-bottom: 0 !important;
    padding-bottom: 0 !important;
  }
  .buscador-container {
    width: 100%;
    margin-left: 0 !important;
    margin-top: 0 !important;
    margin-bottom: 0 !important;
    padding-bottom: 0 !important;
    justify-content: flex-start;
  }
  .buscador-input {
    max-width: 100%;
    min-width: 120px;
    font-size: 0.95rem;
    margin-top: 0 !important;
    margin-bottom: 0 !important;
    padding-bottom: 0 !important;
  }
}
@media (max-width: 600px) {
  .almacen-select-container {
    min-width: 0 !important;
    max-width: 100% !important;
    width: 100% !important;
    margin-bottom: 0 !important;
    padding-bottom: 0 !important;
  }
  .almacen-select-container label,
  .almacen-select-container .p-dropdown {
    margin-bottom: 0 !important;
    padding-bottom: 0 !important;
  }
  .buscador-input {
    max-width: 100%;
    min-width: 100px;
    font-size: 0.9rem;
    padding: 0.3rem 0.5rem;
    margin-top: 0 !important;
    margin-bottom: 0 !important;
    padding-bottom: 0 !important;
  }
}
/* === RESPONSIVE DESIGN igual que ArticuloNewView.vue y LineaNewView.vue === */
@media (max-width: 1024px) {
  .responsive-dialog >>> .p-dialog {
    margin: 0.5rem;
    max-height: 95vh;
  }
  >>> .p-datatable {
    font-size: 0.85rem;
  }
  .panel-header,
  .header-flex {
    flex-direction: column;
    align-items: flex-start;
    gap: 0.5rem;
  }
  .almacen-busqueda-flex {
    flex-direction: column;
    gap: 1rem;
    align-items: stretch;
  }
  .almacen-select-container,
  .buscador-container {
    min-width: 0;
    max-width: 100%;
    width: 100%;
  }
  .buscador-input {
    min-width: 0;
    max-width: 100%;
    width: 100%;
  }
}
@media (max-width: 768px) {
  .toolbar .p-button .p-button-label {
    display: none;
  }
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
  .panel-header,
  .header-flex {
    flex-direction: column;
    align-items: flex-start;
    gap: 0.3rem;
  }
  .header-title-icon {
    gap: 0.3rem;
  }
  .compact-stepper.linear-stepper {
    min-width: 80px;
    max-width: 120px;
  }
  .almacen-busqueda-flex {
    flex-direction: column;
    gap: 0.5rem;
    align-items: stretch;
  }
  .almacen-select-container,
  .buscador-container {
    min-width: 0;
    max-width: 100%;
    width: 100%;
  }
  .buscador-input {
    min-width: 0;
    max-width: 100%;
    width: 100%;
    font-size: 0.95rem;
    padding: 0.4rem 0.6rem;
  }
  >>> .p-panel .p-panel-content {
    padding: 0.5rem !important;
  }
  >>> .p-panel .p-panel-header {
    padding: 0.5rem 0.7rem;
    font-size: 1rem;
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
  >>> .p-button-sm {
    font-size: 0.75rem !important;
    padding: 0.375rem 0.5rem !important;
    min-width: auto !important;
  }
  .step-buttons-row {
    flex-direction: row !important;
    gap: 0.5rem !important;
    align-items: center !important;
    justify-content: center !important;
  }
  .buttons.p-d-flex {
    flex-direction: column !important;
    gap: 0.5rem;
    align-items: stretch !important;
  }
  .p-d-flex.p-jc-end {
    flex-direction: column !important;
    align-items: flex-end !important;
    gap: 0.3rem;
  }
}
@media (max-width: 480px) {
  .toolbar .p-button .p-button-label {
    display: none;
  }
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
  .responsive-dialog >>> .p-dialog-footer {
    padding: 0.5rem 0.75rem;
    justify-content: flex-end;
  }
  .responsive-dialog >>> .p-dialog-footer .p-button {
    width: auto;
    margin-bottom: 0.25rem;
  }
  .panel-header,
  .header-flex {
    flex-direction: column;
    align-items: flex-start;
    gap: 0.2rem;
  }
  .header-title-icon {
    gap: 0.2rem;
  }
  .compact-stepper.linear-stepper {
    min-width: 60px;
    max-width: 80px;
  }
  .almacen-busqueda-flex {
    flex-direction: column;
    gap: 0.3rem;
    align-items: stretch;
  }
  .almacen-select-container,
  .buscador-container {
    min-width: 0;
    max-width: 100%;
    width: 100%;
  }
  .buscador-input {
    min-width: 0;
    max-width: 100%;
    width: 100%;
    font-size: 0.9rem;
    padding: 0.3rem 0.4rem;
  }
  >>> .p-panel .p-panel-content {
    padding: 0.3rem !important;
  }
  >>> .p-panel .p-panel-header {
    padding: 0.3rem 0.5rem;
    font-size: 0.95rem;
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
  >>> .p-button-sm {
    font-size: 0.7rem !important;
    padding: 0.3rem 0.4rem !important;
    min-width: auto !important;
  }
  /* Scroll horizontal para tablas */
  >>> .p-datatable-wrapper {
    overflow-x: auto;
  }
}
.compra-action-buttons-row {
  flex-direction: row !important;
  gap: 0.5rem !important;
  align-items: center !important;
  justify-content: flex-end !important;
}
@media (max-width: 768px) {
  .compra-action-buttons-row {
    flex-direction: row !important;
    gap: 0.5rem !important;
    align-items: center !important;
    justify-content: flex-end !important;
  }
}
.step-buttons-row > .p-button {
  margin-left: 0.5rem;
  margin-right: 0.5rem;
}
</style>

<style scoped>
.reset-buscar-btn {
  margin-left: 0.5rem;
  background: #f5f5f5;
  border: 1px solid #ccc;
  border-radius: 4px;
  padding: 0.35rem 0.6rem;
  cursor: pointer;
  font-size: 1.1rem;
  display: flex;
  align-items: center;
  height: 2.2rem;
  transition: background 0.2s;
}
.reset-buscar-btn i.pi {
  font-size: 1.1rem !important;
  line-height: 1 !important;
}
.reset-buscar-btn:hover {
  background: #e0e0e0;
}
</style>

<style>
.custom-swal-confirm {
  background-color: #28a745 !important;
  color: #fff !important;
  border: none !important;
  box-shadow: none !important;
}
.custom-swal-cancel {
  background-color: #d33 !important;
  color: #fff !important;
  border: none !important;
  box-shadow: none !important;
  margin-right: 0.5rem;
}
.swal2-popup .swal2-styled:focus {
  box-shadow: 0 0 0 2px #28a74555 !important;
}
</style>
