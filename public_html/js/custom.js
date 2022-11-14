//для примера воспользуемся jQuerry и с его помощью навешаем обработчиков на основные действия
$(document).ready(function () {

    //разметим ввод даты, для этого притащили компонент bootstrap-datepicker.js (см. подключние)
    $('.input-daterange input').datepicker({
        format: 'yyyy-mm-dd'
    });

    let ajaxUrl = '/ajax/index.php';//адрес нашего пхп-скрипта для обработки всех запросов с этой страницы

    let ajdata = {//массив, кторый будем передавать обработчику запросом или формой
        cmd : 'getFiltersData'
    };

    //в задании требуется в ответе передать только колонки, указанные в таблице. Заберём их и сохраним в массив
    let tableColumns = [];
    $('#declaration-list thead [data-field]').each(function (){
        tableColumns.push($(this).data('field'));
    });

    //запросим значения для фильтров и сразу поместим их в селекты
    $.ajax({
        type: "POST",
        url: ajaxUrl,
        data: ajdata,
        dataType: "json",
        cache: false,

        success: function(data , status, xhr)
        {
            //при успешном ответе в элементе filtersData будут значения фильтров. Формирование см. в ajax/index.php
            for(let i in data.filtersData.status)
            {
                $('#state').append(
                    $('<option/>')
                        .attr('value', data.filtersData.status[i].id)
                        .text(data.filtersData.status[i].name)
                );
            }
        }
    });

    //отправку формы тоже будем делать аяксом. Обработку см. все в том же ajax/index.php
    $(document).on('submit', '#form-filter', function(e){
        e.preventDefault();
        let fdata = new FormData(this);
        fdata.append("cmd", "sendFiltersData");
        fdata.append("columns", tableColumns);
        $.ajax({
            type: "POST",
            url: ajaxUrl,
            data: fdata,
            dataType: "json",
            processData: false,
            contentType: false,
            cache: false,
            success: function(data , status, xhr) {
                //в ответе придет статус обработки - ОК в параметре 'status' в случае удачи, и что-то другое в случае ошибки
                $('[errorstatus]').remove();//удалим предыдущие выводы об ошибках, если они были
                if("OK" == data.status) {

                    $('#declaration-list tbody tr').remove();//удалим строки, если они остались от предыдущего запроса

                    //теперь переберем массив ответа и занесем с втроки таблицы
                    for (let idx in data.resultData.items) {
                        let tr = $('<tr/>');
                        $('#declaration-list thead tr th').each(function () {
                            let txt = '';
                            let key = $(this).data('field');
                            if (key in data.resultData.items[idx])
                                txt = data.resultData.items[idx][key];
                            tr.append($('<td/>').text(txt));
                        });
                        $('#declaration-list tbody').append(tr);
                    }
                }
                else
                {//что-то пошло не так - на этот случай у нас есть код ошибки в ответе. Создадим красивый алерт с ним
                    $('#declaration-list').before(
                        $('<p class="alert alert-warning" role="alert"/>')
                            .attr('errorstatus', data.status)
                            .text( data.error)
                    );
                }

            }

        });
    });

});