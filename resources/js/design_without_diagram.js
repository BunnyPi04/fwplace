$(document).ready(function() {
    function created() {
        console.log('created');
        let id = $('#select_workspace').val();

        $.ajax({
            url: route('api.get_design_without_diagram', [id]),
            method: 'get',
            success: function(result) {
                let diagram = result;
                let keys = Object.keys(diagram);

                for (let i = 0; i < keys.length; i++) {
                    let color = diagram[keys[i]].color;
                    let data = diagram[keys[i]].data;
                    for (let j = 0; j < data.length; j++) {
                        let cell = $(
                            `.seat-cell[row=${data[j].row}][column=${
                                data[j].column
                            }]`
                        );
                        $(cell).css('background-color', color);
                        $(cell).attr('data-name', keys[i]);
                    }

                    appendAreaList(color, keys[i]);
                }
            }
        });
    }

    created();

    $('.generate').click(function() {
        let row = $('input[name=row]').val();
        let column = $('input[name=column').val();
        let table = document.createElement('table');
        let tbody = document.createElement('tbody');
        tbody.setAttribute('id', 'selectable');
        for (let i = 0; i < row; i++) {
            let tr = document.createElement('tr');
            tr.classList.add('row');
            tr.setAttribute('draggable', false);
            for (let j = 0; j < column; j++) {
                let td = document.createElement('td');
                td.classList.add('seat-cell');
                td.setAttribute('draggable', false);
                td.setAttribute('row', i);
                td.setAttribute('column', j);
                tr.append(td);
            }
            tbody.append(tr);
        }
        table.setAttribute('draggable', false);
        table.append(tbody);
        $('.design-section').html(table);
    });

    $(document).on('mousedown', '.seat-cell', function(event) {
        let flag;
        $(document).on('dragging', '.seat-cell', function(event) {
            $(document).on('mouseenter', '.seat-cell', function() {
                if (flag === 1) {
                    if ($(this).hasClass('cell-selected')) {
                        $(this).attr('class', 'seat-cell');
                        $(this).removeAttr('style');
                    } else {
                        $(this).attr('class', 'seat-cell cell-selected');
                        $(this).removeAttr('style');
                    }
                }
            });
        });
        $(document).on('dragend', '.seat-cell', function(event) {
            $(this).trigger('mouseup');
        });
        if (event.which === 1) {
            flag = 1;
            if ($(this).hasClass('cell-selected')) {
                $(this).attr('class', 'seat-cell');
                $(this).removeAttr('style');
                $(this).removeAttr('data-name');
            } else {
                $(this).attr('class', 'seat-cell cell-selected');
                $(this).removeAttr('style');
                $(this).removeAttr('data-name');
            }
            $(document).on('mouseenter', '.seat-cell', function() {
                if (flag === 1) {
                    if ($(this).hasClass('cell-selected')) {
                        $(this).attr('class', 'seat-cell');
                    } else {
                        $(this).attr('class', 'seat-cell cell-selected');
                        $(this).removeAttr('style');
                        $(this).removeAttr('data-name');
                    }
                }
            });

            $(document).on('mouseup', function() {
                flag = 0;
            });
        }
    });

    function appendAreaList(color, name) {
        $('.area-section').append(
            `<li class="area-list" data-name="${name}">
                <div class="area-color" style="background-color: ${color}"></div>
                <label>${name}</label>
                <a href="javascript:void(0)" class="remove-area">X</a>
            </li>`
        );
        $('.cell-selected').css({
            'background-color': color
        });
        $('.cell-selected').attr('data-name', name);
        $('.cell-selected').attr('class', 'seat-cell area-selected');
    }

    $(document).on('click', '.default-areas', function() {
        if (!checkSelect()) {
            return;
        }
        let color;
        let name;
        if ($(this).hasClass('door')) {
            color = '#757575';
            name = 'door';
        }
        if ($(this).hasClass('path')) {
            color = '#af5c5c';
            name = 'path';
        }
        if ($(this).hasClass('freespace')) {
            color = '#5caf8d';
            name = 'freespace';
        }
        appendAreaList(color, name);
    });

    function removeArea(name, areaList) {
        $(areaList)
            .parents('li')
            .remove();
        let elements = $(`.seat-cell[data-name=${name}]`);
        $(elements).removeAttr('style');
        $(elements).removeAttr('data-name');
        $(elements).attr('class', 'seat-cell');
    }

    $(document).on('click', '.remove-area', function() {
        let param;
        let name = $(this)
            .parents('li')
            .attr('data-name');
        removeArea(name, $(this));
    });

    function checkSelect() {
        if ($('.design-section').children().length == 0) {
            swal(Lang.get('messages.swal_title.generate'));

            return false;
        }
        if ($('.cell-selected').length == 0) {
            swal(Lang.get('messages.swal_title.select_area'));

            return false;
        }

        return true;
    }

    $('#newArea').click(function() {
        if ($('input[type=text][name=name]').val().length < 1) {
            swal(Lang.get('messages.swal_title.input_name'));

            return;
        }

        if (!checkSelect()) {
            return;
        }

        appendAreaList(
            $('input[type=color][name=color]').val(),
            $('input[type=text][name=name]').val()
        );
    });

    function getName() {
        let result = [];
        $('.area-list').each(function() {
            result.push($(this).attr('data-name'));
        });

        return result;
    }

    function getCellArray(elements) {
        let result = {};
        let data = [];
        $(elements).each(function() {
            let cell = {};
            cell['row'] = $(this).attr('row');
            cell['column'] = $(this).attr('column');
            data.push(cell);
        });

        result['color'] = $(elements)
            .first()
            .css('background-color');
        result['data'] = data;

        return result;
    }

    $('#saveDiagram').click(function() {
        let arrayName = getName();
        let sendData = {};
        let len = arrayName.length;
        for (let i = 0; i < len; i++) {
            sendData[arrayName[i]] = getCellArray(
                $(`.area-selected[data-name='${arrayName[i]}']`)
            );
        }
        let id = $('#select_workspace').val();
        let row = $('#row').val();
        let column = $('#column').val();
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
        console.log(row);
        console.log(column);
        console.log(sendData);
        $.ajax({
            url: route('save_design_without_diagram'),
            method: 'POST',
            data: {
                content: sendData,
                workspace_id: id,
                row: row,
                column: column
            },
            success: function(result) {
                swal({
                    type: 'success',
                    title: Lang.get('messages.swal_title.success'),
                    text: result.message
                });
            },
            error: function(result) {
                swal({
                    type: 'error',
                    title: Lang.get('messages.swal_title.error'),
                    text: result.message
                });
            }
        });
    });
});
