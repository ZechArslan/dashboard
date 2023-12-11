$(document).ready(function () {
  var datestart = "";
  var dateend = "";
  var Api;
  var dataRange = [false, true, true, true, true, false];
  $("#token_table thead tr")
    .clone(true)
    .addClass("filters")
    .appendTo("#token_table thead");
  var table = $("#token_table").DataTable({
    orderCellsTop: true,
    scrollX: true,
    // ajax: 'data/objects.json',
    processing: true,
    serverSide: true,
    serverMethod: "post",
    oLanguage: { sProcessing: "<div id='loader'></div>" },

    // ajax: '../../datatable.php', ///Path will change here
    ajax: {
      url: "http://144.76.163.123/dashboard/tokens.php?action=get_all_tokens",

      data: function (d) {
        d.datestart = datestart;
        d.dateend = dateend;
        d.search = {
          value: d.search.value,
          regex: d.search.regex,
        };

        d.columns.forEach(function (column, index) {
          d.columns[index].search = {
            value: column.search.value,
            regex: column.search.regex,
          };
        });

        window["lastpayload"] = d;
      },
    },
    columns: [
      { data: "Id" },
      { data: "Name" },
      { data: "Symbol" },
      // { data: "TransferAmount" },
      {
        data: "Address",
      },
      { data: "IsMainToken" },
      { data: "IsScamToken" },
      {
        data: "Action",
        render: function (e) {
          return `
        <div class="eatherIconcontainer">
           
            <abbr title="View PNL">
                <div class="statisIcon actionsIcons erthIcon edit-modal edit-icon-div" width="20px" height="20px" type="button">
                <img src="./edit.png" alt="" />
                </div>
            </abbr>
        </div> `;
        },
      },
    ],
    dom: "Blfrtip",

    stateSave: true,
    // fixedHeader: {
    //     headerOffset: 40
    // },
    stateSaveParams: function (settings, data) {
      data.search.search = "";
      // data.search.datestart = datestart;
      // data.search.dateend = dateend;
      data.columns.forEach((val) => {
        val.search.search = "";
      });
    },
    columnDefs: [
      { targets: 0, orderable: true, searching: true, width: "10%" },
      { width: "10%", targets: 1 },
      { width: "10%", targets: 2 },
      { width: "10%", targets: 3, orderable: false },
      { width: "10%", targets: 4 },
      // { width: "10%", targets: 5 },
      { width: "10%", targets: 5, orderable: false },
      { width: "10%", targets: 6, orderable: false },
    ],
    language: {
      lengthMenu: "_MENU_",
    },
    lengthMenu: [
      [5, 10, 25, 50, 100, 500],
      [5, 10, 25, 50, 100, 500],
    ],
    pageLength: 10,

    initComplete: function () {
      var api = this.api();
      Api = this.api();
      api
        .columns()
        .eq(0)
        .each(function (colIdx) {
          if (colIdx != 6) {
            var cell = $(".filters th").eq(
              $(api.column(colIdx).header()).index()
            );
            var title = $(cell).text();
            $(cell).html('<input type="text" placeholder="' + title + '" />');
          }
        });
    },
  });
  table.button().add(1, {
    action: function (e, dt, button, config) {
      var tableid = button.attr("aria-controls");
      $.fn.dataTable.ext.search = [];

      Api.columns()
        .eq(0)
        .each(function (colIdx) {
          var inputVal = $(".filters th")
            .eq($(Api.column(colIdx).header()).index())
            .find("input")
            .val();
          Api.column(colIdx).search(inputVal);
        });
      dt.draw();
    },
    text: "Search",
    className: "search-btnTbl",
  });

  var editPopup_bach = $("#edit-trade-modal .modal-content").html();

  $("body").on("click", ".edit-modal", function () {
    var id = $(this).closest("tr").find("td:first").text();

    $("#edit-trade-modal .modal-content").html(editPopup_bach);
    $("body").addClass("oflowhidden");
    $(".edit-trade-modal").toggleClass("show");
    $(".js-example-tags").select2({
      tags: true,
    });

    $.ajax({
      url:
        "http://144.76.163.123/dashboard/tokens.php?action=get_token_tags&token_id=" +
        id,
      method: "GET",
      dataType: "json",
      success: function (data) {
        var select = $("#mySelect");
        select.empty();

        data.forEach(function (option) {
          select.append(new Option(option, option, false, false));
        });

        select.select2({
          tags: true,
        });
        var allOptions = data.map(function (option) {
          return option;
        });
        select.val(allOptions).trigger("change");
      },
      error: function (xhr, status, error) {
        alert(error);
      },
    });

    $("#addTag").click(function () {
      var selectedTags = $("#mySelect").val();
      var jsonData = JSON.stringify(selectedTags);
      $.ajax({
        url:
          "http://144.76.163.123/dashboard/tokens.php?action=update_token_tags&token_id=" +
          id,
        method: "POST",

        data: $.param({ tags: jsonData }),
        dataType: "json",
        success: function (data) {
          alert(data);
        },
        error: function (xhr, status, error) {
          alert(error);
        },
      });
    });
  });

  $("body").on("click", ".edit-trade-modal .cross-svg", function (e) {
    $("body").removeClass("oflowhidden");
    $(".edit-trade-modal").removeClass("show");
  });

  $("#token_table").on("click", ".tradeAddressTab", function (e) {
    window.open(`https://etherscan.io/address/${e.currentTarget.id}`, "_blank");
  });
  $("#token_table").on("click", ".homeTableAddress", function (e) {
    e.currentTarget.innerHTML.toString().includes("...")
      ? (e.currentTarget.innerHTML = e.currentTarget.id)
      : (e.currentTarget.innerHTML =
          e.currentTarget.id.substr(0, 9) +
          "..." +
          e.currentTarget.id.substr(e.currentTarget.id.length - 9));
  });
});
