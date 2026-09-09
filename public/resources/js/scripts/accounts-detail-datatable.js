(function(){
    document.addEventListener('DOMContentLoaded', function(){
        if (window.jQuery && $.fn.DataTable) {
            var table = $('#transactions-table').DataTable({
                responsive: true,
                scrollX: true,
                paging: true,
                pageLength: 10,
                lengthChange: true,
                ordering: true,
                order: [[0, 'desc']],
                fixedColumns: {
                    leftColumns: 1
                },
                dom: 'Bfrtip',
                buttons: [
                    { extend: 'copyHtml5', text: 'Copiar' },
                    { extend: 'excelHtml5', text: 'Excel' },
                    { extend: 'pdfHtml5', text: 'PDF' },
                    { extend: 'print', text: 'Imprimir' }
                ],
                language: {
                    decimal: ',',
                    thousands: '.',
                    processing: 'Procesando...',
                    search: 'Buscar global:',
                    lengthMenu: 'Mostrar _MENU_ registros',
                    info: 'Mostrando _START_ a _END_ de _TOTAL_ registros',
                    infoEmpty: 'Mostrando 0 a 0 de 0 registros',
                    infoFiltered: '(filtrado de _MAX_ registros)',
                    infoPostFix: '',
                    loadingRecords: 'Cargando...',
                    zeroRecords: 'No se encontraron registros',
                    emptyTable: 'No hay transacciones disponibles',
                    paginate: {
                        first: 'Primero',
                        previous: 'Anterior',
                        next: 'Siguiente',
                        last: 'Último'
                    },
                    aria: {
                        sortAscending: ': activar para ordenar ascendente',
                        sortDescending: ': activar para ordenar descendente'
                    },
                    buttons: {
                        copyTitle: 'Copiado al portapapeles',
                        copySuccess: { _: '%d líneas copiadas', 1: '1 línea copiada' },
                        copy: 'Copiar',
                        excel: 'Excel',
                        pdf: 'PDF',
                        print: 'Imprimir'
                    }
                },
                initComplete: function () {
                    this.api().columns().every(function () {
                        var column = this;
                        $('input', column.footer()).on('keyup change clear', function () {
                            if (column.search() !== this.value) {
                                column.search(this.value).draw();
                            }
                        });
                    });
                }
            });
        }
    });
})();
