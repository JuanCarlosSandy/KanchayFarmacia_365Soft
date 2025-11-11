<template>
    <main class="main">
        <div class="loading-overlay" v-if="isLoading">
            <div class="loading-container">
                <div class="spinner"></div>
                <div class="loading-text">LOADING...</div>
            </div>
        </div>
        <div class="p-p-2 p-mx-auto" style="max-width: 100%;">
            <!-- Ejemplo de tabla Listado -->
            <div class="card">

                <div class="card-header">
                    <ul class="nav nav-tabs">
                        <li class="nav-item">
                            <a class="nav-link" href="#" :class="{ active: activeTab === 0 }" @click="activeTab = 0">
                                <i class="fa fa-align-justify"></i> Modo de trabajo
                            </a>
                        </li>
                        <!--<li class="nav-item">
                            <a class="nav-link" href="#" :class="{ active: activeTab === 1 }" @click="activeTab = 1">
                                <i class="fa fa-cogs"></i> Valores por omisión
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#" :class="{ active: activeTab === 2 }" @click="activeTab = 2">
                                <i class="fa fa-file"></i> Datos fiscales
                            </a>
                        </li>-->
                        <li class="nav-item">
                            <a class="nav-link" href="#" :class="{ active: activeTab === 3 }" @click="activeTab = 3">
                                <i class="fa fa-user-check"></i> Autorizar Descuentos
                            </a>
                        </li>
                    </ul>
                </div>

                <div class="card-body" v-show="activeTab === 3">
                    <h5>Autorizaciones de Descuento</h5>
                    <div class="form-group">
                        <label for="vendedor">Seleccione Vendedor:</label>
                        <select v-model="idUsuarioSeleccionado" class="form-control">
                            <option disabled value="">Seleccione un vendedor</option>
                            <option v-for="usuario in listaUsuarios" :key="usuario.id" :value="usuario.id">
                                {{ usuario.usuario }}
                            </option>
                        </select>
                    </div>

                    <div class="form-group mt-3">
                        <label>¿Puede aplicar descuentos?</label><br>
                        <input type="checkbox" v-model="puedeDescontar" />
                    </div>

                    <Button class="p-button-success mt-3" label="Guardar Autorización" @click="guardarAutorizacion()" />
                </div>

                <!-- contenido1 -->
                <div class="card-body" v-show="activeTab === 0">
                    <div class="row mb-3">
                        <div class="col">
                        <label for="yearInput">Gestión:</label>
                        <div class="input-group">
                            <input
                            type="number"
                            id="yearInput"
                            class="form-control"
                            v-model="selectedYear"
                            min="1900"
                            max="2100"
                            />
                        </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col">
                            <button class="btn btn-primary" @click="guardar()">Guardar</button>
                            <button class="btn btn-secondary" @click="cancelar">Cancelar</button>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col">
                        <label for="venta1Input">Venta 1:</label>
                        <div class="input-group">
                            <input
                            type="number"
                            id="venta1Input"
                            class="form-control"
                            v-model.number="precio_uno"
                            min="0"
                            step="0.01"
                            placeholder="0.00"
                            />
                            <span class="input-group-text">%</span>
                        </div>
                        </div>
                    </div>

                    <!-- Campo Venta 2 -->
                    <div class="row mb-4">
                        <div class="col">
                        <label for="venta2Input">Venta 2:</label>
                        <div class="input-group">
                            <input
                            type="number"
                            id="venta2Input"
                            class="form-control"
                            v-model.number="precio_dos"
                            min="0"
                            step="0.01"
                            placeholder="0.00"
                            />
                            <span class="input-group-text">%</span>
                        </div>
                        </div>
                    </div>

                    <!-- Botón Guardar -->
                    <div class="row">
                        <div class="col">
                        <Button
                            label="Guardar"
                            icon="pi pi-save"
                            class="p-button-success"
                            :loading="isLoading"
                            @click="guardarPrecios"
                            />
                        </div>
                    </div>
                    <!--<div class="row">
                        <div class="col">
                            <label for="opcion3">Almacén Predeterminado: </label>
                            <select v-model="almacenSeleccionado" class="form-control">
                                <option v-for="almacen in almacenes" :key="almacen.id" :value="almacen.id">
                                    {{ almacen.nombre_almacen }}
                                </option>
                            </select>
                        </div>
                        <div class="col">
                            <div class="form-group">
                                <label for="opcion2">Limite de descuento:</label>
                                <select id="opcion2" class="form-control" v-model="limiteDescuento">
                                    <option value="Precio mayorista">Precio mayorista</option>
                                    <option value="Precio minorista">Precio minorista</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col">
                            <div class="form-group">
                                <label for="opcion1">Valuación inventario:</label>
                                <select class="form-control" v-model="valuacionInventario">
                                    <option value="Ninguno">Ninguno</option>
                                    <option value="costo_reposicion">Costo por Reposicion</option>
                                    <option value="opcion1_valor3">Valor 3</option>
                                </select>
                            </div>
                        </div>
                        <div class="col">
                            <div class="form-group">
                                <label for="opcion2">Backup automático:</label>
                                <select id="opcion2" class="form-control" v-model="backupAutomatico">
                                    <option value="Nunca">Nunca</option>
                                    <option value="opcion2_valor2">Valor 2</option>
                                    <option value="opcion2_valor3">Valor 3</option>
                                </select>
                            </div>
                        </div>
                        <div class="col">
                            <label for="rutaBackup">Ruta de backup:</label>

                            <div class="form-group">
                                <input class="form-control" type="text" id="rutaBackup" v-model="rutaBackup" />
                            </div>

                            <button type="button" @click="sacarBackupBaseDatos()" class="btn btn-info">
                                <i class="icon-doc"></i>&nbsp;Backup
                            </button>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="opcion1">Saldos negativos:</label>
                                <select id="opcion1" class="form-control" v-model="saldosNegativos">
                                    <option value="0">No</option>
                                    <option value="1">Si</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="opcion1">Permitir devolución:</label>
                                <select id="opcion1" class="form-control" v-model="devolucion">
                                    <option value="1">Si</option>
                                    <option value="0">No</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col">
                            <div class="form-group">
                                <label for="" class="font-weight-bold">Moneda principal <span class="text-danger">*</span></label>

                                <select class="form-control" v-model="idMonedaPrincipal" >
                                    <option disabled value="-1">Selecciona una moneda</option>
                                    <option v-for="moneda in arrayMonedas" :key="moneda.id" :value="moneda.id">{{ moneda.nombre }}</option>
                                </select>
                            </div>
                        </div>
                        <div class="col">
                            <div class="form-group">
                                <label for="" class="font-weight-bold">Moneda Ventas <span class="text-danger">*</span></label>

                                <select class="form-control" v-model="idMonedaVenta" >
                                    <option disabled value="-1">Selecciona una moneda</option>
                                    <option v-for="moneda in arrayMonedas" :key="moneda.id" :value="moneda.id">{{ moneda.nombre }}</option>
                                </select>
                            </div>
                        </div>
                        <div class="col">
                            <div class="form-group">
                                <label for="" class="font-weight-bold">Moneda Compras <span class="text-danger">*</span></label>

                                <select class="form-control" v-model="idMonedaCompra" >
                                    <option disabled value="-1">Selecciona una moneda</option>
                                    <option v-for="moneda in arrayMonedas" :key="moneda.id" :value="moneda.id">{{ moneda.nombre }}</option>
                                </select>
                            </div>
                        </div>
                        <div class="col">

                            <label for="opcion3">Mostrar saldos stock:</label>

                            <div class="form-group">
                                <select id="separadorDecimales" class="form-control" v-model="mostrarSaldosStock">
                                    <option value="0">No</option>
                                    <option value="1">Si</option>
                                </select>
                            </div>
                        </div>
                        <div class="col">
                            <label for="opcion3">Actualizar Iva:</label>

                            <div class="form-group">
                                <select id="separadorDecimales" class="form-control" v-model="actualizarIVA">
                                    <option value="0">No</option>
                                    <option value="1">Si</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <hr/>

                    <div class="row">
                        <div class="col-md-4">

                    <label>Configuración de precios</label>
                    <br/>
                        </div>
                    </div> 
                    <div v-for="precio in precios" :key="precio.id" class="row ">
          
                        <div class="col-md-4">
                            <label>Etiqueta de Precio:</label>
                            <div class="input-group" style="width: 100%">
                                <input type="text" class="form-control" placeholder="Porcentaje" :value="precio.nombre_precio">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label>% Margen:</label>
                            <div class="input-group" style="width: 100%">
                                <input type="number" class="form-control" :value="precio.porcentage">
                            </div>
                        </div>
                                      <div class="col-md-4">
                            <label>Mostrar:</label>
                            <div class="input-group" style="width: 100%">
                                <select class="form-control" v-model="precio.condicion">
                                    <option :value=1>Sí</option>
                                    <option :value=0>No</option>
                                </select>
                            </div>
                        </div>
                        <br/>
                        <br/>
                        <br/>


                    </div>  -->              




                    
                </div>




                <!-- fin del contenido 1 -->
                <!-- contenido 2 -->
                <div class="card-body" v-show="activeTab === 1">
                    <div class="row">
                        <div class="col">
                            <div class="form-group">
                                <label for="opcion1">Opción 1:</label>
                                <select id="opcion1" class="form-control" v-model="opcion1">
                                    <option value="">Seleccionar</option>
                                    <option value="opcion1_valor1">Valor 1</option>
                                    <option value="opcion1_valor2">Valor 2</option>
                                    <option value="opcion1_valor3">Valor 3</option>
                                </select>
                            </div>
                        </div>
                        <div class="col">
                            <div class="form-group">
                                <label for="opcion2">Opción 2:</label>
                                <select id="opcion2" class="form-control" v-model="opcion2">
                                    <option value="">Seleccionar</option>
                                    <option value="opcion2_valor1">Valor 1</option>
                                    <option value="opcion2_valor2">Valor 2</option>
                                    <option value="opcion2_valor3">Valor 3</option>
                                </select>
                            </div>
                        </div>
                        <div class="col">
                            <div class="form-group">
                                <label for="opcion3">Opción 3:</label>
                                <select id="opcion3" class="form-control" v-model="opcion3">
                                    <option value="">Seleccionar</option>
                                    <option value="opcion3_valor1">Valor 1</option>
                                    <option value="opcion3_valor2">Valor 2</option>
                                    <option value="opcion3_valor3">Valor 3</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col">
                            <button class="btn btn-primary" @click="guardar">Guardar</button>
                            <button class="btn btn-secondary" @click="cancelar">Cancelar</button>
                        </div>
                    </div>
                </div>

                <!-- fin del contenido 2-->
                <!-- contenido 3 -->
                <div class="card-body" v-show="activeTab === 2">
                   
                </div>

                <!-- fin del contenido 3-->
            </div>
        </div>
         <!--Inicio del modal agregar/actualizar-->
         <div class="modal " tabindex="-1" :class="{'mostrar': modal}" role="dialog" aria-labelledby="myModalLabel" style="display: none;" aria-hidden="true">
            <div class="modal-dialog modal-primary modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-body">
                        <form action="" method="post" enctype="multipart/form-data" class="form-horizontal">
                            <div class="form-group row">
                                <label class="col-md-3 form-control-label" for="text-input">Nombre Precio(*)</label>
                                <div class="col-md-9">
                                    <input type="text" v-model="nombre_precio" class="form-control" placeholder="Nombre Precio"> 
                                </div>
                            </div>
                            <div class="form-group row">
                                <label class="col-md-3 form-control-label" for="text-input">Porcentaje(*)</label>
                                <div class="col-md-9">
                                    <input type="text" v-model="porcentage" class="form-control" placeholder="Valor de porcentaje"> 
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" @click="cerrarModal()">Cerrar</button>
                        <button type="button" v-if="tipoAccion==1" class="btn btn-primary" @click="registrarPrecio()">Guardar</button>
                    </div>
                </div>
            </div>
        </div>       

    </main>
</template>
<script>
import Dropdown from 'primevue/dropdown';
import Swal from 'sweetalert2';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Paginator from 'primevue/paginator';
import Card from 'primevue/card';
import InputText from 'primevue/inputtext';
import Button from 'primevue/button';
import Panel from 'primevue/panel';
import Steps from 'primevue/steps';
import Dialog from 'primevue/dialog';
import Message from 'primevue/message';
import Tag from 'primevue/tag';
import SelectButton from 'primevue/selectbutton';
import InputNumber from 'primevue/inputnumber';

export default {
    components: {
        Dropdown,
        DataTable,
        Column,
        Button,
        Paginator,
        Card,
        InputText,
        Button,
        Panel,
        Steps,
        Message,
        Dialog,
        Tag,
        SelectButton,
        InputNumber,
    },
    data() {
        return {
            isLoading: false,
            idMonedaVenta:-1,
            idMonedaCompra:1,
            idMonedaPrincipal:-1,

         
            
            arrayMonedas:'',
            monedaSeleccionada:-1,

            id: 0,
            selectedYear: '',
            almacenPredeterminado: '',
            codigoProducto: '',
            consultarAlmacenes: '',
            limiteDescuento: '',
            maximoDescuento: 0,
            valuacionInventario: '',
            backupAutomatico: '',
            rutaBackup: '',
            saldosNegativos: '',
            monedaTrabajo: '',
            separadorDecimales: 'Punto',
            etiquetaPrecio1: 'Por mayor',
            mostrarEtiquetaPrecio1: 'No',
            margenEtiquetaPrecio1: 30,
            etiquetaPrecio2: 'Por mayor',
            mostrarEtiquetaPrecio2: 'No',
            margenEtiquetaPrecio2: 30,
            etiquetaPrecio3: 'Por mayor',
            mostrarEtiquetaPrecio3: 'No',
            margenEtiquetaPrecio3: 30,
            etiquetaPrecio4: 'Por mayor',
            mostrarEtiquetaPrecio4: 'No',
            margenEtiquetaPrecio4: 30,
            mostrarCostos: '',
            mostrarProveedor: '',
            mostrarSaldosStock: '',
            actualizarIVA: '',
            vendedorAsignado: '',
            devolucion: '',
            registroClienteObligatorio: '',
            editarNroDoc: '',
            buscarClientePorCodigo: '',
            mensajeCheckBox: '',
            activeTab: 0, // Inicialmente, se muestra el contenido de la primera pestaña
            opcion1:'',
            opcion2:'',
            opcion3:'',
            precios:[],
            // modal: 0,
            modal : 0,
            tituloModal : '',
            tipoAccion : 0,
            nombre_precio : '',
            porcentage : '',
            condicion : 1,
            almacenes: [],
            almacenSeleccionado: '',

            idUsuarioSeleccionado: '',
            puedeDescontar: false,
            listaUsuarios: [], // Aquí cargarás los usuarios
            precio_uno: 0,
            precio_dos: 0,
        };
    },
    computed: {
    idMonedaSeleccionada() {
      const monedaSeleccionada = this.arrayMonedas.find(moneda => moneda.nombre === this.monedaSeleccionada);
      return monedaSeleccionada ? monedaSeleccionada.id : null;
    }
  },
    methods: {
        toastSuccess(mensaje) {
            this.$toasted.show(
                `
            <div style="height: 50px;font-size:16px;">
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
        async cargarPrecios() {
            try {
                const response = await axios.get("/configuracion/porcentajes");
                const precios = response.data;

                const venta1 = precios.find((p) => p.nombre_precio === "VENTA 1");
                const venta2 = precios.find((p) => p.nombre_precio === "VENTA 2");

                this.precio_uno = venta1 ? venta1.porcentage : 0;
                this.precio_dos = venta2 ? venta2.porcentage : 0;
            } catch (error) {
                console.error("❌ Error al cargar precios:", error);
            }
            },

            async guardarPrecios() {
                const me = this; // 🔹 para mantener el contexto
                me.isLoading = true; // 🔹 activa el loading

                try {
                    const payload = [
                        { nombre_precio: "VENTA 1", porcentage: me.precio_uno },
                        { nombre_precio: "VENTA 2", porcentage: me.precio_dos },
                    ];

                    const response = await axios.put("/configuracion/porcentajes", payload);

                    me.isLoading = false; // 🔹 desactiva el loading

                    // ✅ Mostrar toast personalizado
                    me.toastSuccess("Porcentajes actualizados correctamente");

                    console.log("📦 Datos guardados:", response.data);

                } catch (error) {
                    me.isLoading = false;
                    console.error("❌ Error al guardar precios:", error);

                    // ❌ Mostrar toast de error (usando tu helper también si lo tenés)
                    if (typeof me.toastError === "function") {
                        me.toastError("No se pudo actualizar los porcentajes");
                    } else {
                        me.$toast.add({
                            severity: "error",
                            summary: "Error al guardar",
                            detail: "No se pudo actualizar los porcentajes.",
                            life: 4000,
                        });
                    }
                }
            },

        async listarMonedas() {
            try {
                this.isLoading = true; // Activar loading
                const url = '/moneda/selectMoneda';
                const response = await axios.get(url);
                this.arrayMonedas = response.data.monedas;
            } catch (error) {
                console.error("Error al listar monedas:", error);
                swal("Error", "No se pudieron cargar las monedas", "error");
            } finally {
                setTimeout(() => {
                    this.isLoading = false; // Desactivar loading
                }, 500);
            }
        },
        async datosConfiguracion() {
            try {
                this.isLoading = true; // Activar loading
                const url = '/configuracion/editar';
                const response = await axios.get(url);
                const respuesta = response.data;
                
                // Asignar valores de configuración
                this.id = respuesta.configuracionTrabajo.id;
                this.idMonedaCompra = respuesta.configuracionTrabajo.idMonedaCompra;
                this.idMonedaVenta = respuesta.configuracionTrabajo.idMonedaVenta;
                this.idMonedaPrincipal = respuesta.configuracionTrabajo.idMonedaPrincipal;
                this.almacenPredeterminado = respuesta.configuracionTrabajo.almacenSeleccionado;
                this.selectedYear = respuesta.configuracionTrabajo.gestion;
                this.codigoProducto = respuesta.configuracionTrabajo.codigoProductos;
                this.limiteDescuento = respuesta.configuracionTrabajo.limiteDescuento;
                this.maximoDescuento = respuesta.configuracionTrabajo.maximoDescuento;
                this.valuacionInventario = respuesta.configuracionTrabajo.valuacionInventario;
                this.backupAutomatico = respuesta.configuracionTrabajo.backupAutomatico;
                this.rutaBackup = respuesta.configuracionTrabajo.rutaBackup;
                this.saldosNegativos = respuesta.configuracionTrabajo.saldosNegativos;
                this.monedaTrabajo = respuesta.configuracionTrabajo.monedaTrabajo;
                this.separadorDecimales = respuesta.configuracionTrabajo.separadorDecimales;
                this.mostrarCostos = respuesta.configuracionTrabajo.mostrarCostos;
                this.mostrarProveedor = respuesta.configuracionTrabajo.mostrarProveedores;
                this.mostrarSaldosStock = respuesta.configuracionTrabajo.mostrarSaldosStock;
                this.actualizarIVA = respuesta.configuracionTrabajo.actualizarIva;
                this.vendedorAsignado = respuesta.configuracionTrabajo.vendedorAsignado;
                this.devolucion = respuesta.configuracionTrabajo.permitirDevolucion;
                this.editarNroDoc = respuesta.configuracionTrabajo.editarNroDoc;
                this.registroClienteObligatorio = respuesta.configuracionTrabajo.registroClienteObligatorio;
                this.buscarClientePorCodigo = respuesta.configuracionTrabajo.buscarClientePorCodigo;
            } catch (error) {
                console.error("Error al cargar configuración:", error);
                swal("Error", "No se pudo cargar la configuración", "error");
            } finally {
                setTimeout(() => {
                    this.isLoading = false; // Desactivar loading
                }, 500);
            }
        },
            async guardar() {
            try {
                this.isLoading = true; // Activar loading
                
                // Guardar cambios en precios
                await Promise.all(this.precios.map(precio => this.guardarCambios(precio)));

                // Guardar configuración general
                await axios.put('/configuracion/actualizar', {
                    'idMonedaCompra': this.idMonedaCompra,
                    'idMonedaVenta': this.idMonedaVenta,
                    'idMonedaPrincipal': this.idMonedaPrincipal,
                    'selectedYear': this.selectedYear,
                    'almacenPredeterminado': this.almacenSeleccionado,
                    'codigoProducto': this.codigoProducto,
                    'limiteDescuento': this.limiteDescuento,
                    'maximoDescuento': this.maximoDescuento,
                    'valuacionInventario': this.valuacionInventario,
                    'backupAutomatico': this.backupAutomatico,
                    'rutaBackup': this.rutaBackup,
                    'saldosNegativos': this.saldosNegativos,
                    'monedaTrabajo': this.monedaTrabajo,
                    'separadorDecimales': this.separadorDecimales,
                    'mostrarCostos': this.mostrarCostos,
                    'mostrarProveedor': this.mostrarProveedor,
                    'mostrarSaldosStock': this.mostrarSaldosStock,
                    'actualizarIVA': this.actualizarIVA,
                    'vendedorAsignado': this.vendedorAsignado,
                    'devolucion': this.devolucion,
                    'editarNroDoc': this.editarNroDoc,
                    'registroClienteObligatorio': this.registroClienteObligatorio,
                    'buscarClientePorCodigo': this.buscarClientePorCodigo,
                    'id': this.id
                });

                swal("GUARDADO", "La configuración ha sido actualizada", "success");
            } catch (error) {
                console.error("Error al guardar:", error);
                swal("Error", "No se pudo guardar la configuración", "error");
            } finally {
                this.isLoading = false; // Desactivar loading
            }
        },
        async sacarBackupBaseDatos() {
            try {
                this.isLoading = true; // Activar loading
                const response = await axios.get('/backup');
                
                if (response.data.exito) {
                    swal("Éxito!", response.data.exito, "success");
                } else {
                    swal("Error!", response.data.error, "error");
                }
            } catch (error) {
                console.error("Error al realizar backup:", error);
                swal("Error", "No se pudo realizar el backup", "error");
            } finally {
                setTimeout(() => {
                    this.isLoading = false; // Desactivar loading
                }, 500);
            }
        },
        cancelar() {
            // Lógica para cancelar la acción
        },
        listarPrecio() {
            let me = this;
            var url = '/preciosactivos';
            axios.get(url).then(function(response) {
                var respuesta = response.data;
                me.precios = respuesta.precio.data.map(precio => ({
                ...precio,
                habilitado: precio.habilitado === 1 // Convertir el valor numérico en un booleano
                }));
            }).catch(function(error) {
                console.log(error);
            });
        },
        registrarPrecio(){
            let me = this;

            axios.post('/precios/registrar',{
                'nombre_precio': this.nombre_precio,
                'porcentage': this.porcentage,
            }).then(function (response) {
                me.cerrarModal();
                console.log(response)
                me.listarPrecio();
                me.datosConfiguracion();
                //me.listarPrecio(1,'','nombre_precio');
            }).catch(function (error) {
                console.log(error);
            });
        },
        guardarCambios(precio) {
            //let me = this;
            let accion = precio.condicion ? 'activar' : 'desactivar';
            axios.put(`/precios/${precio.id}/${accion}`)
        },
        abrirModal(modelo, accion, data = []){
            switch(modelo){
                case "precioss":
                {
                    switch(accion){
                        case 'registrar':
                        {
                            this.modal = 1;
                            this.tituloModal = 'Registrar Precio';
                            this.tipoAccion = 1;
                            this.nombre_precio = '';
                            this.porcentage = '';
                            break;
                        }
                        case 'actualizar':
                        {
                            break;
                        }
                    }
                }
            }
        },
        cerrarModal(){
        this.modal=0;
        this.tituloModal='';
        this.nombre_precio='';
        this.porcentage='';
        },

        async obtenerAlmacenes() {
            try {
                const response = await axios.get('/configuracion/ruta-a-tu-endpoint-para-obtener-almacenes');
                this.almacenes = response.data; 
                console.log(this.almacenes);
            } catch (error) {
                console.error('Error al obtener los almacenes:', error);
            }
        },

        async obtenerUsuarios() {
            try {
                const response = await axios.get('/api/user/selectUsuario'); // Ajusta tu ruta si es diferente
                this.listaUsuarios = response.data.usuarios;
            } catch (error) {
                console.error('Error al obtener usuarios:', error);
            }
        },

        async guardarAutorizacion() {
            if (!this.idUsuarioSeleccionado) {
                alert('Debe seleccionar un vendedor.');
                return;
            }

            try {
                await axios.post('/api/autorizaciones-descuento', {
                    idusuario: this.idUsuarioSeleccionado,
                    puede_descontar: this.puedeDescontar ? 1 : 0,
                });
                swal(
                            'AUTORIAZADO',
                            'La autorizacion a sido realizada.',
                            'success'
                        )            
                    } catch (error) {
                console.error('Error al guardar autorización:', error);
                alert('Ocurrió un error al guardar.');
            }
        }
    },
    watch: {
        precio_uno(nuevo, viejo) {
            if (viejo !== undefined && nuevo !== viejo) {
            this.$toast.add({
                severity: "info",
                summary: "Campo modificado",
                detail: "Has cambiado el valor de Venta 1.",
                life: 2500,
            });
            }
        },
        precio_dos(nuevo, viejo) {
            if (viejo !== undefined && nuevo !== viejo) {
            this.$toast.add({
                severity: "info",
                summary: "Campo modificado",
                detail: "Has cambiado el valor de Venta 2.",
                life: 2500,
            });
            }
        },
        },

    created() {
        this.obtenerAlmacenes();
    },

async mounted() {
    
    try {
        this.isLoading = true; // Activar loading
        await Promise.all([
            this.cargarPrecios(),
            this.listarMonedas(),
            this.listarPrecio(),
            this.obtenerUsuarios(),
            this.datosConfiguracion()
        ]);
    } catch (error) {
        console.error("Error en la carga inicial:", error);
        swal("Error", "Error al cargar los datos iniciales", "error");
    } finally {
        this.isLoading = false; // Desactivar loading
    }
},
};
</script>

<style>

    .nav-link.active {
        background-color: #f8f9fa;
    }
    .mostrar{
    display: list-item !important;
    opacity: 1 !important;
    position: absolute !important;
    background-color: #3c29297a !important;
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
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
</style>