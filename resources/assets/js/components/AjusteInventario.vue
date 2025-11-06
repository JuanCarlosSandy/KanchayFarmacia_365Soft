<template>
  <main class="main">
    <div class="loading-overlay" v-if="isLoading">
      <div class="loading-container">
        <div class="spinner"></div>
        <div class="loading-text">LOADING...</div>
      </div>
    </div>
    <Panel>
      <template #header>
        <div class="panel-header">
          <i class="pi pi-shopping-cart panel-icon"></i>
          <h4 class="panel-title">AJUSTE DE INVENTARIO</h4>
        </div>
      </template>

      <div class="toolbar-container">
        <div class="search-bar">
          <span class="p-input-icon-left">
            <i class="pi pi-search" />
            <InputText
              v-model="buscar"
              class="form-control"
              placeholder="Texto a buscar"
            />
          </span>
        </div>
        <div class="toolbar">
          <Button
            :label="mostrarLabel ? 'Reset' : ''"
            icon="pi pi-refresh"
            @click="resetBusqueda"
            class="p-button-help p-button-sm"
          />
          <Button
            :label="mostrarLabel ? 'Nuevo' : ''"
            icon="pi pi-plus"
            @click="abrirModal('articulo', 'registrar')"
            class="p-button-secondary p-button-sm"
          />
        </div>
      </div>

      <DataTable
        :value="arrayAjuste"
        class="p-datatable-sm p-datatable-gridlines"
        responsiveLayout="scroll"
      >
        <Column field="nombre_almacen" header="ALMACEN" />
        <Column field="nombre" header="ARTICULO" />
        <Column field="cantidad" header="CANTIDAD" />
        <Column field="tipo" header="TIPO BAJA" />
        <Column field="created_at" header="FECHA Y HORA" />
      </DataTable>

      <Paginator
        :rows="pagination.per_page"
        :totalRecords="pagination.total"
        :first="(pagination.current_page - 1) * pagination.per_page"
        @page="onPageChange"
      />
    </Panel>

    <Dialog
      :visible.sync="modal"
      modal
      :header="tituloModal"
      :closable="true"
      @hide="cerrarModal"
      class="responsive-dialog"
      :containerStyle="dialogContainerStyle"
    >
      <form @submit.prevent="enviarFormulario">
        <div class="form-group row">
          <div class="col-md-6">
            <label class="font-weight-bold" for="almacen">
              Almacén <span class="text-danger">*</span>
            </label>
            <Dropdown
              id="almacen"
              v-model="idAlmacenSeleccionado"
              :options="arrayAlmacenes"
              optionLabel="nombre_almacen"
              optionValue="id"
              placeholder="Seleccione un almacén"
              class="form-control"
              @change="limpiarProductosSeleccionados"
            />
            <small class="p-error" v-if="errores.idAlmacenSeleccionado">
              <strong>{{ errores.idAlmacenSeleccionado }}</strong>
            </small>
          </div>
          <div class="col-md-6">
            <label class="font-weight-bold">
              Productos <span class="text-danger">*</span>
            </label>
            <Button
              label="Agregar Producto"
              icon="pi pi-plus"
              class="p-button-success form-control"
              @click="abrirModal2('Productos')"
              :disabled="!idAlmacenSeleccionado"
              style="justify-content: center;"
            />
            <small class="text-muted d-block" v-if="!idAlmacenSeleccionado">
              Primero seleccione un almacén
            </small>
            <small class="p-error" v-if="errores.idproducto">
              <strong>{{ errores.idproducto }}</strong>
            </small>
          </div>
          <div class="col-md-6">
            <label class="font-weight-bold">
              Motivo de Baja <span class="text-danger">*</span>
            </label>
            <div class="p-inputgroup">
              <InputText
                placeholder="Seleccione un Motivo"
                v-model="motivoseleccionado.nombre"
                :disabled="true"
                :class="{ 'p-invalid': errores.idmotivo }"
                @input="asignarCampos()"
              />
              <Button
                icon="pi pi-ellipsis-h"
                class="p-button-primary"
                @click="abrirModal2('Motivo')"
              />
            </div>
            <small class="p-error" v-if="errores.idmotivo">
              <strong>{{ errores.idmotivo }}</strong>
            </small>
          </div>
        </div>
        
        <div class="form-group row">
          <!-- Tabla de productos seleccionados -->
          <div class="form-group row" v-if="hayProductosSeleccionados">
            <div class="col-md-12">
              <label class="font-weight-bold">
                Productos Seleccionados
              </label>
              <DataTable 
                :value="productosSeleccionados" 
                class="p-datatable-sm p-datatable-gridlines"
                :paginator="productosSeleccionados.length > 5"
                :rows="5"
                responsiveLayout="scroll"
              >
                <Column field="nombre" header="Producto" style="width: 30%">
                  <template #body="slotProps">
                    <div>
                      <strong>{{ slotProps.data.nombre }}</strong>
                      <br>
                      <small class="text-muted">
                        <i class="pi pi-building"></i> {{ slotProps.data.nombre_proveedor || 'Sin proveedor' }}
                      </small>
                    </div>
                  </template>
                </Column>
                
                <Column field="stock_actual" header="Stock Actual" style="width: 15%">
                  <template #body="slotProps">
                    <span class="badge badge-info">{{ slotProps.data.stock_actual }}</span>
                  </template>
                </Column>
                
                <Column header="Cantidad a Dar de Baja" style="width: 20%">
                  <template #body="slotProps">
                    <InputText
                      type="number"
                      v-model="slotProps.data.cantidad_ajuste"
                      class="form-control"
                      placeholder="0"
                      :min="0"
                      :max="slotProps.data.stock_actual"
                      @input="actualizarStockRestante(slotProps.data)"
                      style="width: 100px;"
                    />
                    <small 
                      class="p-error d-block" 
                      v-if="Number(slotProps.data.cantidad_ajuste) > Number(slotProps.data.stock_actual)"
                    >
                      Mayor al stock
                    </small>
                  </template>
                </Column>
                
                <Column field="stock_restante" header="Stock Restante" style="width: 15%">
                  <template #body="slotProps">
                    <span 
                      class="badge"
                      :class="slotProps.data.stock_restante < 0 ? 'badge-danger' : 'badge-success'"
                    >
                      {{ slotProps.data.stock_restante }}
                    </span>
                  </template>
                </Column>
                
                <Column header="Acciones" style="width: 8%">
                  <template #body="slotProps">
                    <Button
                      icon="pi pi-trash"
                      class="p-button-danger p-button-sm"
                      @click="eliminarProducto(slotProps.index)"
                      title="Eliminar producto"
                    />
                  </template>
                </Column>
              </DataTable>
              
              <!-- Resumen totales -->
              <div class="mt-3 p-3" style="background-color: #f8f9fa; border-radius: 5px;">
                <div class="row" style="display: flex; justify-content: space-between;">                  
                  <div class="col-md-4">
                    <strong><i class="pi pi-shopping-cart"></i> Productos: {{ productosSeleccionados.length }}</strong>
                  </div>
                  <div class="col-md-4">
                    <strong><i class="pi pi-box"></i> Total Unidades: {{ totalUnidadesAjuste }}</strong>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>  
      </form>
      
      <template #footer>
        <Button
          label="Cerrar"
          icon="pi pi-times"
          class="p-button-danger p-button-sm"
          @click="cerrarModal"
          type="button"
        />
        <Button
          v-if="tipoAccion == 1"
          class="p-button-success p-button-sm"
          label="Procesar Ajuste"
          icon="pi pi-check"
          @click="enviarFormulario"
          type="button"
          :disabled="!hayProductosSeleccionados || !puedeEnviarFormulario()"
          :loading="isLoading"
        />
      </template>
    </Dialog>

    <Dialog
      :visible.sync="modal2"
      modal
      :header="tituloModal2"
      :closable="true"
      @hide="cerrarModal2"
      class="responsive-dialog"
      :containerStyle="dialogContainerStyle"
    >
      <div class="toolbar-container">
        <div class="search-bar">
          <span class="p-input-icon-left">
            <i class="pi pi-search" />
            <InputText
              v-if="tituloModal2 == 'Motivo'"
              v-model="buscarA"
              @keyup="listarMotivo(1, buscarA, criterioA)"
              class="form-control"
              placeholder="Buscar motivo..."
            />
            <InputText
              v-if="tituloModal2 == 'Productos'"
              v-model="buscarA"
              @keyup="
                listarProducto(
                  1,
                  buscarA,
                  criterioA,
                  idAlmacenSeleccionado,
                  false
                )
              "
              class="form-control"
              placeholder="Buscar producto..."
            />
          </span>
        </div>

        <div class="toolbar">
          <Button
            v-if="tituloModal2 == 'Motivo'"
            :label="mostrarLabel ? 'Reset' : ''"
            icon="pi pi-refresh"
            @click="
              buscarA = '';
              listarMotivo(1, '', criterioA);
            "
            title="Limpiar"
            class="p-button-help p-button-sm"
          />
          <Button
            v-show="tituloModal2 == 'Motivo'"
            :label="mostrarLabel ? 'Nuevo' : ''"
            icon="pi pi-plus"
            class="p-button-secondary p-button-sm"
            @click="abrirModal3('Marca', 'registrarMar')"
          />

          <Button
            v-if="tituloModal2 == 'Productos'"
            :label="mostrarLabel ? 'Reset' : ''"
            icon="pi pi-refresh"
            class="p-button-help p-button-sm"
            @click="
              buscarA = '';
              listarProducto(1, '', criterioA, idAlmacenSeleccionado, false);
            "
            title="Limpiar"
          />
        </div>
      </div>

      <div class="table-responsive">
        <DataTable
          v-if="tituloModal2 == 'Productos'"
          :value="arrayBuscador"
          class="p-datatable-sm p-datatable-gridlines"
          responsiveLayout="scroll"
        >
          <Column header="Seleccionar" style="width: 10%">
            <template #body="slotProps">
              <Button
                :icon="productosSeleccionados.find(p => p.id === slotProps.data.id) ? 'pi pi-check-circle' : 'pi pi-plus-circle'"
                :class="productosSeleccionados.find(p => p.id === slotProps.data.id) ? 'p-button-success' : 'p-button-primary'"
                class="p-button-sm"
                @click="seleccionar(slotProps.data)"
                :disabled="productosSeleccionados.find(p => p.id === slotProps.data.id)"
                :title="productosSeleccionados.find(p => p.id === slotProps.data.id) ? 'Ya seleccionado' : 'Seleccionar producto'"
              />
            </template>
          </Column>
          <Column field="codigo" header="Código" />
          <Column field="nombre" header="Producto">
            <template #body="slotProps">
              <div>
                <strong>{{ slotProps.data.nombre }}</strong>
                <br>
                <small class="text-muted">{{ slotProps.data.nombre_proveedor || 'Sin proveedor' }}</small>
              </div>
            </template>
          </Column>
          <Column field="stock_total" header="Stock Disponible">
            <template #body="slotProps">
              <span class="badge badge-primary">{{ slotProps.data.stock_total || 0 }}</span>
            </template>
          </Column>
        </DataTable>
        
        <DataTable
          v-else-if="tituloModal2 == 'Motivo'"
          :value="arrayBuscador"
          class="p-datatable-sm p-datatable-gridlines"
          responsiveLayout="scroll"
        >
          <Column header="Seleccionar" style="width: 15%">
            <template #body="slotProps">
              <Button
                icon="pi pi-check"
                class="p-button-success p-button-sm"
                @click="seleccionar(slotProps.data)"
              />
            </template>
          </Column>
          <Column field="nombre" header="Motivo de Baja" />
        </DataTable>
      </div>

      <div v-if="tituloModal2 == 'Motivo'">
        <Paginator
          :rows="pagination.per_page"
          :totalRecords="pagination.total"
          :first="(pagination.current_page - 1) * pagination.per_page"
          @page="
            (event) => {
              const page = Math.floor(event.first / event.rows) + 1;
              cambiarPaginaMarca(page, buscar, criterio);
            }
          "
        />
      </div>
      <div v-else-if="tituloModal2 == 'Productos'">
        <Paginator
          :rows="pagination.per_page"
          :totalRecords="pagination.total"
          :first="(pagination.current_page - 1) * pagination.per_page"
          @page="
            (event) => {
              const page = Math.floor(event.first / event.rows) + 1;
              listarProducto(
                page,
                buscarA,
                criterio,
                idAlmacenSeleccionado,
                false
              );
            }
          "
        />
      </div>
      <template #footer>
        <Button
          label="Cerrar"
          icon="pi pi-times"
          class="p-button-danger p-button-sm"
          @click="cerrarModal2"
          type="button"
        />
      </template>
    </Dialog>

    <Dialog
      :visible.sync="modal3"
      modal
      :header="tituloModal3"
      :closable="true"
      @hide="cerrarModal3"
      class="responsive-dialog"
      :containerStyle="dialogContainerStyle"
    >
      <div v-if="tituloModal2 !== 'Proveedors'">
        <form class="form-horizontal">
          <div
            v-if="tituloModal2 !== 'Grupos' && tituloModal2 !== 'Lineas'"
            class="form-group row"
          >
            <label class="col-md-3 form-control-label" for="text-input">
              Nombre
            </label>
            <div class="col-md-9">
              <InputText
                type="text"
                v-model="nombre"
                class="form-control1"
                placeholder="Ingrese nombre del motivo"
              />
            </div>
          </div>
        </form>
      </div>
      <template #footer>
        <Button
          label="Cerrar"
          icon="pi pi-times"
          class="p-button-secondary p-button-sm"
          @click="cerrarModal3"
          type="button"
        />
        <Button
          v-if="tipoAccion2 == 5"
          class="p-button-primary p-button-sm"
          label="Guardar"
          icon="pi pi-check"
          @click="registrarMarca"
          type="button"
        />
        <Button
          v-if="tipoAccion2 == 6"
          class="p-button-primary p-button-sm"
          label="Actualizar"
          icon="pi pi-check"
          @click="actualizarMarca"
          type="button"
        />
      </template>
    </Dialog>
  </main>
</template>


<script>
import Button from "primevue/button";
import InputText from "primevue/inputtext";
import Dropdown from "primevue/dropdown";
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import Dialog from "primevue/dialog";
import Paginator from "primevue/paginator";
import { esquemaArticulos, esquemaInventario } from "../constants/validations";
import VueBarcode from "vue-barcode";
import Panel from "primevue/panel";

export default {
  components: {
    Button,
    InputText,
    Dropdown,
    DataTable,
    Column,
    Dialog,
    Paginator,
    barcode: VueBarcode,
    Panel,
  },
  data() {
    return {
      mostrarLabel: true,
      buscar: "",
      isLoading: false,
      idAlmacenSeleccionado: null,
      categoriaSeleccionada: "",

      tipo_stock: "paquetes",
      idarticulo: 0,
      fechaVencimientoSeleccion: "0",
      arrayAlmacenes: [],
      AlmacenSeleccionado: null,
      productosSeleccionados: [], 
      datosFormulario: {
        cantidad: 0,
        idtipobaja: null,
        producto: null,
        idAlmacenSeleccionado: null,
      },

      stock_actual: 0,
      stock_restante: 0,

      errores: {},
      monedaPrincipal: [],

      criterioA: "nombre",
      buscarA: "",
      tituloModal2: "",
      motivoseleccionado: [],
      industriaseleccionada: [],
      productoseleccionado: [],
      proveedorseleccionada: [],
      gruposeleccionada: [],
      nombre_grupo: "",

      modal2: false,
      idcategoria: 0,
      idmarca: 0,
      idindustria: 0,
      idproveedor: 0,
      idgrupo: 0,
      idmedida: 0,
      nombre_categoria: "",
      nombre_proveedor: "",
      //id:'',//aumente 7 julio
      codigo: "",
      nombre: "",
      nombre_generico: "",
      //validaion para inputs

      lineaseleccionadaVacio: false,
      marcaseleccionadaVacio: false,
      //aumente esto
      unidad_envase: 0,
      precio_costo_unid: 0,
      precio_costo_paq: 0,
      //hasta aqui
      precios: [],
      precio: "", //aumente 5julio

      //aumento 13_junio
      precio_uno: 0,
      precio_dos: 0,
      precio_tres: 0,
      precio_cuatro: 0,
      //hasta aqui

      //--------hasta aqui-----------------
      //--grupo--
      nombre_grupo: "",
      //---hasta aqui
      precio_venta: 0,
      costo_compra: 0,

      stock: 5,
      descripcion: "",
      fotografia: "",
      fotoMuestra: null,
      arrayAjuste: [],
      arrayBuscador: [],
      modal: 0,

      tituloModal: "",
      tipoAccion: 0,
      tipoAccion2: 0,
      //------registro industia, marcas--
      modal3: 0,
      tituloModal3: "",
      marca_id: 0,
      condicion: 1,
      nombre_industria: "",
      //--------hasta aqui---
      pagination: this.createPaginationObject(),
      offset: {
        pagination: 3,
      },
      criterio: "articulos.nombre", // Por defecto
      categoria: "", // Nueva categoría seleccionada

      //CONFIGURACIONES
      mostrarSaldosStock: "",
      mostrarProveedores: "",
      mostrarCostos: "",
      rolUsuario: "",

      descripcion_medida: "",
      medidaseleccionada: [],
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
    isActived: function() {
      return this.pagination.current_page;
    },
    pagesNumber: function() {
      return this.calculatePages(this.pagination, this.offset.pagination);
    },
    
    totalUnidadesAjuste() {
      return this.productosSeleccionados.reduce((total, producto) => {
        const cantidad = parseInt(producto.cantidad_ajuste) || 0;
        return total + (cantidad > 0 ? cantidad : 0);
      }, 0);
    },
    
    hayProductosSeleccionados() {
      return this.productosSeleccionados.length > 0;
    }
  },
  //añadido en fecha 14/03/25
  // Corrección de los watchers
  watch: {
    buscar: {
      handler: function(val) {
        if (this._debounceBuscar) clearTimeout(this._debounceBuscar);
        this._debounceBuscar = setTimeout(() => {
          this.listarAjuste(1, val, "");
        }, 400);
      },
    },
    // Para el producto seleccionado
    productoseleccionado: {
      handler(newVal) {
        if (newVal && newVal.id) {
          // Actualizar el ID del producto en datosFormulario
          this.datosFormulario.producto = newVal.id;
          // Llamar a obtenerStock
          this.obtenerStock();
        }
      },
      deep: true,
    },

    // Para el almacén seleccionado
    idAlmacenSeleccionado: function(newVal) {
      this.AlmacenSeleccionado = newVal; // Sincronizar ambas variables
      this.datosFormulario.idAlmacenSeleccionado = newVal; // Mantener siempre actualizado el id en el formulario
      
      if (newVal !== this.idAlmacenAnterior) {
        this.limpiarProductosSeleccionados();
        this.idAlmacenAnterior = newVal;
      }

      if (newVal && this.productoseleccionado && this.productoseleccionado.id) {
        this.obtenerStock();
      }
    },

    motivoseleccionado: {
      handler(newVal) {
        this.asignarCampos();
      },
      deep: true,
    },

    // Para la cantidad
    "datosFormulario.cantidad": function(newVal) {
      this.actualizarStock();
    },
  },

  methods: {
    resetBusqueda() {
      this.buscar = "";
      this.listarAjuste(1, this.buscar || "", "", this.categoria);
    },
    handleResize() {
      this.mostrarLabel = window.innerWidth > 768; // cambia según breakpoint deseado
    },
    onPageChange(event) {
      const page = Math.floor(event.first / event.rows) + 1;
      this.cambiarPagina(page);
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
          duration: 4000,
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
          duration: 4000,
        }
      );
    },
    asignarCampos() {
      this.datosFormulario.producto = this.productoseleccionado.id;
      this.datosFormulario.idtipobaja = this.motivoseleccionado.id;
      this.datosFormulario.idAlmacenSeleccionado = this.idAlmacenSeleccionado;
    },
    async validarCampo() {
      this.asignarCampos();
    },

    async enviarFormulario() {
      const productosConCantidad = this.productosSeleccionados.filter(producto => {
        const cantidad = parseInt(producto.cantidad_ajuste) || 0;
        return cantidad > 0;
      });

      // Validar que las cantidades no excedan el stock
      const productosInvalidos = productosConCantidad.filter(producto => {
        const cantidad = parseInt(producto.cantidad_ajuste) || 0;
        return cantidad > producto.stock_actual;
      });

      if (productosInvalidos.length > 0) {
        this.toastError("Algunos productos tienen cantidades mayores al stock actual");
        return;
      }

      try {
        // Actualizar temporalmente productosSeleccionados solo con los que tienen cantidad
        const productosOriginales = [...this.productosSeleccionados];
        this.productosSeleccionados = productosConCantidad;
        
        await this.registrarAjusteMultiple();
        
        this.productosSeleccionados = productosOriginales;
        
      } catch (error) {
        console.error("Error al registrar el ajuste múltiple:", error);
      }
    },

    obtenerConfiguracionTrabajo() {
      // Utiliza Axios para realizar la solicitud al backend
      axios
        .get("/configuracion")
        .then((response) => {
          console.log(response);
        })
        .catch((error) => {
          console.error("Error al obtener configuración de trabajo:", error);
        });
    },

    calculatePages: function(paginationObject, offset) {
      if (!paginationObject.to) {
        return [];
      }

      var from = paginationObject.current_page - offset;
      if (from < 1) {
        from = 1;
      }

      var to = from + offset * 2;
      if (to >= paginationObject.last_page) {
        to = paginationObject.last_page;
      }

      var pagesArray = [];
      while (from <= to) {
        pagesArray.push(from);
        from++;
      }
      return pagesArray;
    },
    createPaginationObject() {
      return {
        total: 0,
        current_page: 0,
        per_page: 0,
        last_page: 0,
        from: 0,
        to: 0,
      };
    },

    //-------------hasta qui calcular -----------
    seleccionar(selected) {
      if (this.tituloModal2 == "Motivo") {
        this.motivoseleccionado = selected;
        this.marcaseleccionadaVacio = false;
      } else if (this.tituloModal2 == "Productos") {
        if (selected.condicion == 1) {
          this.agregarProductoSeleccionado(selected);
        } else if (selected.condicion == 0) {
          this.advertenciaInactiva("Productos");
        }
      }

      this.arrayBuscador = [];
      this.modal2 = false;
    },

    cerrarModal2() {
      this.arrayBuscador = [];
      this.modal2 = false;
      this.buscarA = "";
    },

    async listarProducto(
      page,
      buscar,
      criterio,
      idAlmacen,
      showLoading = true
    ) {
      try {
        if (showLoading) this.isLoading = true; // Activar loading solo si se pide
        let me = this;
        let url = `/articuloAjusteInven?page=${page}&buscar=${buscar}&criterio=${criterio}&idAlmacen=${idAlmacen}&categoria=${me.categoriaSeleccionada}`;

        const response = await axios.get(url);
        me.arrayBuscador = response.data.articulos.data;
        me.pagination = response.data.pagination;
      } catch (error) {
        console.error("Error al listar los productos:", error);
        swal("Error", "No se pudieron cargar los productos", "error");
      } finally {
        if (showLoading) {
          setTimeout(() => {
            this.isLoading = false; // Desactivar loading
          }, 500);
        }
      }
    },

    seleccionarCategoria(categoria) {
      this.categoriaSeleccionada = categoria; // Almacenar la categoría seleccionada
      this.listarProducto(
        1,
        this.buscarA,
        this.criterioA,
        this.idAlmacenSeleccionado
      ); // Actualizar la lista con la nueva categoría
    },

    selectAlmacen() {
      let me = this;
      var url = "/almacen/selectAlmacen";
      axios
        .get(url)
        .then(function(response) {
          var respuesta = response.data;
          me.arrayAlmacenes = respuesta.almacenes;
        })
        .catch(function(error) {
          console.log(error);
        });
    },

    abrirModal2(titulo) {
      if (titulo === "Motivo") {
        this.listarMotivo(1, "", "nombre");
        this.modal2 = true;
        this.tituloModal2 = titulo;
        this.marcaseleccionadaVacio = false;
      } else if (titulo === "Productos") {
        if (!this.idAlmacenSeleccionado) {
          swal({
            icon: "warning",
            title: "Almacén no seleccionado",
            text: "Primero seleccione un almacén válido",
          });
          return;
        }
        this.listarProducto(
          1,
          this.buscarA,
          this.criterioA,
          this.idAlmacenSeleccionado
        );
        this.modal2 = true;
        this.tituloModal2 = titulo;
        this.lineaseleccionadaVacio = false;
      }
    },

    async agregarProductoSeleccionado(producto) {
      const productoExistente = this.productosSeleccionados.find(p => p.id === producto.id);
      
      if (productoExistente) {
        this.toastError("Este producto ya está en la lista");
        return;
      }

      try {
        const stockData = await this.obtenerStockProducto(producto.id, this.idAlmacenSeleccionado);
        
        const productoConStock = {
          ...producto,
          stock_actual: stockData.stock_actual || 0,
          cantidad_ajuste: 0,
          stock_restante: stockData.stock_actual || 0
        };
        
        this.productosSeleccionados.push(productoConStock);
        
      } catch (error) {
        console.error("Error al obtener stock del producto:", error);
        this.toastError("Error al obtener información del producto");
      }
    },

    async obtenerStockProducto(productoId, almacenId) {
      try {
        const response = await axios.get("/ajuste-inventario/obtenerStock", {
          params: {
            producto: productoId,
            almacen: almacenId,
          },
        });
        return response.data;
      } catch (error) {
        console.error("Error al obtener stock:", error);
        return { stock_actual: 0 };
      }
    },

    actualizarStockRestante(producto) {
      const cantidad = parseInt(producto.cantidad_ajuste) || 0;
      producto.stock_restante = Math.max(0, producto.stock_actual - cantidad);
    },

    eliminarProducto(index) {
      const producto = this.productosSeleccionados[index];
      this.productosSeleccionados.splice(index, 1);
    },

    limpiarProductosSeleccionados() {
      if (this.productosSeleccionados.length > 0) {
        this.productosSeleccionados = [];
      }
    },

    puedeEnviarFormulario() {
      if (!this.idAlmacenSeleccionado) return false;
      if (!this.motivoseleccionado.id) return false;
      if (this.productosSeleccionados.length === 0) return false;
      
      // Al menos un producto debe tener cantidad > 0
      const tieneProductosConCantidad = this.productosSeleccionados.some(producto => {
        const cantidad = parseInt(producto.cantidad_ajuste) || 0;
        return cantidad > 0;
      });
      
      return tieneProductosConCantidad;
    },

    validarFormularioMultiple() {
      if (!this.idAlmacenSeleccionado) return false;
      if (!this.motivoseleccionado.id) return false;
      if (this.productosSeleccionados.length === 0) return false;
      
      // TODOS los productos deben tener cantidad > 0 y válida
      return this.productosSeleccionados.every(producto => {
        const cantidad = parseInt(producto.cantidad_ajuste) || 0;
        return cantidad > 0 && cantidad <= producto.stock_actual;
      });
    },

    async registrarAjusteMultiple() {
      try {
        this.isLoading = true;
        
        const ajustesData = {
          almacen_id: this.idAlmacenSeleccionado,
          motivo_id: this.motivoseleccionado.id,
          productos: this.productosSeleccionados.map(producto => ({
            producto_id: producto.id,
            cantidad: parseInt(producto.cantidad_ajuste),
            stock_anterior: producto.stock_actual
          }))
        };

        const response = await axios.post("/ajuste/registrar-multiple", ajustesData, {
          headers: {
            "Content-Type": "application/json",
          },
        });

        this.cerrarModal();
        await this.listarAjuste(1, "", "");
        this.toastSuccess("Ajuste de Inventario registrado correctamente");        
      } catch (error) {
        console.error("Error:", error);
        throw error;
      } finally {
        this.isLoading = false;
      }
    },

    formatearPrecio(precio) {
      if (!precio) return "0.00";
      return new Intl.NumberFormat('es-BO', {
        style: 'currency',
        currency: 'BOB'
      }).format(precio);
    },

    almacenSeleccionado() {},

    listarAlmacenes(page, buscar, criterio) {
      let me = this;
      var url = `/almacen?page=${page}&buscar=${buscar}&criterio=${criterio}`;
      axios
        .get(url)
        .then(function(response) {
          var respuesta = response.data;
          me.arrayBuscador = respuesta.almacenes.data;
          me.pagination = respuesta.pagination;
        })
        .catch(function(error) {
          console.log(error);
        });
    },

    async listarAjuste(page, buscar, criterio) {
      try {
        let me = this;
        var url = `/ajusteinv?page=${page}&buscar=${buscar || ""}&categoria=${
          this.categoria
        }`;

        const response = await axios.get(url);
        var respuesta = response.data;
        me.arrayAjuste = respuesta.ajuste.data;
        me.pagination = respuesta.pagination;
      } catch (error) {
        console.error("Error al listar artículos:", error);
      }
    },

    filtrarPorCategoria(categoria) {
      this.categoria = categoria; // Establece la categoría seleccionada
      this.listarAjuste(1, this.buscar, ""); // Recarga la lista con la categoría aplicada
    },

    listarMarca(page, buscar, criterio) {
      let me = this;
      var url =
        "/marca?page=" + page + "&buscar=" + buscar + "&criterio=" + criterio;

      axios
        .get(url)
        .then(function(response) {
          var respuesta = response.data;

          me.arrayBuscador = respuesta.marcas.data;
          me.pagination = respuesta.pagination;
        })
        .catch(function(error) {
          console.log(error);
        });
    },

    listarMotivo(page, buscar, criterio) {
      let me = this;
      var url =
        "/motivos?page=" + page + "&buscar=" + buscar + "&criterio=" + criterio;
      axios
        .get(url)
        .then(function(response) {
          var respuesta = response.data;
          console.log(respuesta);
          me.arrayBuscador = respuesta.motivos.data;
          me.pagination = respuesta.pagination;
        })
        .catch(function(error) {
          console.log("ERRORES", error);
        });
    },

    cambiarPagina(page) {
      let me = this;
      //Actualiza la página actual
      me.pagination.current_page = page;
      //Envia la petición para visualizar la data de esa página
      me.listarAjuste(page, me.buscar || "", "", me.categoria);
    },

    cambiarPaginaMarca(page, buscar, criterio) {
      let me = this;
      //Actualiza la página actual
      me.pagination.current_page = page;
      me.listarMotivo(page, buscar, criterio);
      //Envia la petición para visualizar la data de esa página
    },
    cambiarPaginaLinea(page, buscar, criterio, idAlmacenSeleccionado) {
      let me = this;
      //Actualiza la página actual
      me.pagination.current_page = page;
      me.listarProducto(page, buscar, criterio, idAlmacenSeleccionado);
      //Envia la petición para visualizar la data de esa página
    },

    // Método para obtener el stock actual 14/03/25
    async obtenerStock() {
      try {
        this.isLoading = true; // Activar loading
        const productoId =
          this.datosFormulario.producto ||
          (this.productoseleccionado ? this.productoseleccionado.id : null);
        const almacenId =
          this.idAlmacenSeleccionado || this.AlmacenSeleccionado;

        if (!productoId || !almacenId) {
          this.stock_actual = 0;
          this.stock_restante = 0;
          return;
        }

        const response = await axios.get("/ajuste-inventario/obtenerStock", {
          params: {
            producto: productoId,
            almacen: almacenId,
          },
        });

        this.stock_actual = response.data.stock_actual;
        this.actualizarStock();
      } catch (error) {
        console.error("Error al obtener el stock:", error);
        console.error(
          "Detalles del error:",
          error.response ? error.response.data : error.message
        );
        this.stock_actual = 0;
        this.stock_restante = 0;
      } finally {
        setTimeout(() => {
          this.isLoading = false; // Desactivar loading
        }, 500);
      }
    },
    actualizarStock() {
      const cantidad = parseInt(this.datosFormulario.cantidad) || 0;
      this.stock_restante = Math.max(0, this.stock_actual - cantidad);
    },

    calcularPrecioValorMoneda(precio) {
      return (precio * parseFloat(this.monedaPrincipal)).toFixed(2);
    },

    async registrarAjuste(data) {
      let me = this;
      try {
        this.isLoading = true; // Activar loading
        var formulario = new FormData();
        for (var key in data) {
          if (data.hasOwnProperty(key)) {
            formulario.append(key, data[key]);
          }
        }

        const response = await axios.post("/ajuste/registrar", formulario, {
          headers: {
            "Content-Type": "multipart/form-data",
          },
        });

        me.idarticulo = response.data.id;
        me.cerrarModal();
        await me.listarAjuste(1, "", "");
      } catch (error) {
        console.error(error);
        me.toastError("Error al registrar el ajuste de inventario");
      } finally {
        this.isLoading = false; // Desactivar loading
      }
    },

    advertenciaInactiva(nombre) {
      swal({
        title: "Opción Inactiva",
        text: "No puede seleccionar esta opción porque está inactiva.",
        type: "warning",
        confirmButtonColor: "#3085d6",
        confirmButtonText: "Aceptar",
        confirmButtonClass: "btn btn-success",
        buttonsStyling: false,
      }).then(() => {
        this.abrirModal2(nombre);
      });
    },

    //#################registro industria############
    registrarMarca() {
      let me = this;

      axios
        .post("/motivo/registrar", {
          nombre: this.nombre,
        })
        .then(function(response) {
          me.cerrarModal3();
          //me.modal3=0;
          me.listarMotivo(1, "", "id");
        })
        .catch(function(error) {
          console.log(error);
        });
    },

    cerrarModal() {
      this.modal = 0;
      this.tituloModal = "";
      this.cantidad = 0;
      this.idtipobaja = "";
      this.producto = "";
      this.buscarA = "";      
      this.productosSeleccionados = [];
    },

    abrirModal(modelo, accion, data = []) {
      switch (modelo) {
        case "articulo": {
          switch (accion) {
            case "registrar": {
              this.modal = 1;
              this.tituloModal = "REGISTRAR AJUSTE DE INVENTARIO";
              this.tipoAccion = 1;
              this.fotografia = "";

              this.datosFormulario = {
                cantidad: 0,
                idtipobaja: null,
                producto: null,
                idAlmacenSeleccionado: null,
              };

              this.idAlmacenSeleccionado = null;
              this.categoriaSeleccionada = "";
              this.productoseleccionado = [];
              this.motivoseleccionado = [];
              this.buscarA = "";

              this.productosSeleccionados = [];

              this.errores = {};
              break;
            }
          }
        }
      }
    },

    cerrarModal3() {
      this.modal3 = 0;
      this.tituloModal3 = "";
      this.nombre = "";
    },

    abrirModal3(modelo3, accion3, data = []) {
      switch (modelo3) {
        case "Marca": {
          switch (accion3) {
            case "registrarMar": {
              this.modal3 = 1;
              this.tituloModal3 = "Registrar Motivo de Bajass";
              this.nombre = "";
              this.tipoAccion2 = 5;
              break;
            }
            case "actualizar": {
              this.modal3 = 1;
              this.tituloModal3 = "Actualizar marca";
              this.tipoAccion2 = 6;
              this.marca_id = data["id"];
              this.nombre = data["nombre"];
              this.condicion = data["condicion"];
              break;
            }
          }
        }
      }
    },

    datosConfiguracion() {
      let me = this;
      var url = "/configuracion";

      axios
        .get(url)
        .then(function(response) {
          var respuesta = response.data;
          me.mostrarSaldosStock =
            respuesta.configuracionTrabajo.mostrarSaldosStock;
          me.mostrarCostos = respuesta.configuracionTrabajo.mostrarCostos;
          me.mostrarProveedores =
            respuesta.configuracionTrabajo.mostrarProveedores;

          me.monedaPrincipal = [
            respuesta.configuracionTrabajo.valor_moneda_principal,
            respuesta.configuracionTrabajo.simbolo_moneda_principal,
          ];
        })
        .catch(function(error) {
          console.log(error);
        });
    },
    recuperarIdRol() {
      this.rolUsuario = window.userData.rol;
    },
  },
  async mounted() {
    this.handleResize();
    window.addEventListener("resize", this.handleResize);
    try {
      await Promise.all([
        this.selectAlmacen(),
        this.recuperarIdRol(),
        this.datosConfiguracion(),
        this.obtenerConfiguracionTrabajo(),
        this.listarAjuste(1, this.buscar, ""),
      ]);
    } catch (error) {
      console.error("Error en la carga inicial:", error);
      swal("Error", "Error al cargar los datos iniciales", "error");
    }
  },
  beforeUnmount() {
    window.removeEventListener("resize", this.handleResize);
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

.panel-header {
  display: flex;
  align-items: center;
}
.panel-title {
  margin: 0;
  padding-left: 5px;
}

/* Responsive Dialog Styles */
.responsive-dialog >>> .p-dialog {
  margin: 0.75rem;
  max-height: 90vh;
  overflow-y: auto;
}

.responsive-dialog >>> .p-dialog-content {
  overflow-x: auto;
  padding: 0.75rem 1rem;
}

.responsive-dialog >>> .p-dialog-header {
  padding: 0.75rem 1.5rem;
  font-size: 1.1rem;
}

.responsive-dialog >>> .p-dialog-footer {
  padding: 0.5rem 1.5rem;
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
  margin-right: 1rem;
}

/* Formulario compacto - Reducir espacios entre campos */
.form-compact >>> .p-field {
  margin-bottom: 0.25rem !important;
}

>>> .p-fluid .p-field {
  margin-bottom: 0.25rem;
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
  text-align: left;
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
  .toolbar .p-button .p-button-label {
    display: none;
  }

  .responsive-dialog >>> .p-dialog {
    margin: 0.25rem;
    max-height: 98vh;
  }

  .responsive-dialog >>> .p-dialog-content {
    padding: 0.5rem 0.75rem;
  }

  .responsive-dialog >>> .p-dialog-header {
    padding: 0.5rem 1rem;
    font-size: 1rem;
  }

  .responsive-dialog >>> .p-dialog-footer {
    padding: 0.4rem 1rem;
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

  >>> .p-inputtext,
  >>> .p-dropdown,
  >>> .p-inputnumber-input,
  >>> .p-multiselect,
  >>> .p-inputtextarea {
    font-size: 0.9rem;
    padding: 0.5rem;
  }

  >>> .p-button-sm {
    font-size: 0.75rem !important;
    padding: 0.375rem 0.5rem !important;
    min-width: auto !important;
  }

  .toolbar >>> .p-button-sm {
    font-size: 0.75rem !important;
    padding: 0.375rem 0.5rem !important;
  }

  .search-bar .p-inputtext-sm {
    padding: 0.35rem 0.5rem 0.35rem 2.5rem !important;
    font-size: 0.85rem !important;
  }
}

/* Extra Small Mobile */
@media (max-width: 480px) {
  .toolbar .p-button .p-button-label {
    display: none;
  }

  .responsive-dialog >>> .p-dialog {
    margin: 0.1rem;
    max-height: 99vh;
  }

  .responsive-dialog >>> .p-dialog-content {
    padding: 0.4rem 0.5rem;
  }

  .responsive-dialog >>> .p-dialog-header {
    padding: 0.4rem 0.75rem;
    font-size: 0.95rem;
  }

  .responsive-dialog >>> .p-dialog-footer {
    padding: 0.3rem 0.75rem;
    justify-content: flex-end;
  }

  .responsive-dialog >>> .p-dialog-footer .p-button {
    width: auto;
    margin-bottom: 0.25rem;
  }

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

  .toolbar >>> .p-button-sm {
    font-size: 0.75rem !important;
    padding: 0.375rem 0.5rem !important;
  }

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

  .required-icon {
    font-size: 0.7rem;
  }

  .optional-icon {
    font-size: 0.55rem;
  }

  >>> .p-inputtext,
  >>> .p-dropdown,
  >>> .p-inputnumber-input,
  >>> .p-multiselect,
  >>> .p-inputtextarea {
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

.p-dialog-mask {
  z-index: 9990 !important;
}
.p-dialog {
  z-index: 9990 !important;
}
/*Panel*/
.ingreso-panel {
  margin-bottom: 1rem;
}
.panel-header {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  width: 100%;
}
.panel-icon {
  color: #000000;
  font-size: 1.2rem;
}
.panel-title {
  margin: 0;
  font-size: 1.1rem;
  font-weight: 600;
  color: #1f2937;
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
</style>
