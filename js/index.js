$(document).ready(function () {
  $(".toggle-menu").click(function () {
    $(".sidebar").toggleClass("active");
  });
  $.post(
    "http://144.76.163.123/dashboard/datatable.php",
    {
      blocks: true,
    },
    function (data, status) {
      // data = JSON.parse(data);
      // console.log(data,);
      $("#sblock").html(data[0]);
      $("#eblock").html(data[1]);
      // alert("Data: " + data + "\nStatus: " + status);
    }
  );
  var dataRange = [false, true, true, true, true, false];
  var Api;
  var datestart = "";
  var dateend = "";
  $('input[name="daterange"]').on(
    "cancel.daterangepicker",
    function (ev, picker) {
      // $('input[name="daterange"]').daterangepicker({ startDate: '11/10/2022', endDate: '11/10/2022' });
      $(this)
        .data("daterangepicker")
        .setStartDate(moment().format("MM-DD-YYYY")); //date now
      $(this).data("daterangepicker").setEndDate(moment().format("MM-DD-YYYY")); //date now
      datestart = "";
      dateend = "";
    }
  );
  var startt = "04/15/2022";
  // var endt = '11/15/2022'
  var enddt = new Date();
  var endt =
    enddt.getMonth() + 1 + "/" + enddt.getDate() + "/" + enddt.getFullYear();
  console.log("endt", endt);
  $('input[name="daterange"]').daterangepicker(
    {
      opens: "left",
      dateFormat: "dd/mm/yy",
      // startDate:  startt, //moment(),
      // endDate: endt, //moment().add(20, 'day'),
      minDate: startt, // moment(),
      maxDate: endt, // moment().add(20, 'day'),
    },
    function (start, end, label) {
      datestart = start.format("YYYY-MM-DD");
      dateend = end.format("YYYY-MM-DD");

      // $(this).data('daterangepicker').setStartDate(startt); //date now
      // $(this).data('daterangepicker').setEndDate(endt);//date now
      console.log(
        "A new date selection was made: " +
          start.format("YYYY-MM-DD") +
          " to " +
          end.format("YYYY-MM-DD")
      );
    }
  );
  const currentDate = new Date();
  const formattedDate = currentDate.toLocaleDateString("en-US", {
    month: "2-digit",
    day: "2-digit",
    year: "numeric",
  });
  // console.log(formattedDate);
  $('input[name="daterange"]')
    .data("daterangepicker")
    .setStartDate(moment("04/15/2022").format("MM-DD-YYYY")); //date now
  $('input[name="daterange"]')
    .data("daterangepicker")
    .setEndDate(moment(formattedDate).format("MM-DD-YYYY")); //date now

  $("#dt_table thead tr")
    .clone(true)
    .addClass("filters")
    .appendTo("#dt_table thead");

  var table = $("#dt_table").DataTable({
    orderCellsTop: true,
    scrollX: true,
    // ajax: 'data/objects.json',
    processing: true,
    serverSide: true,
    serverMethod: "post",
    oLanguage: { sProcessing: "<div id='loader'></div>" },

    ajax: {
      url: "http://144.76.163.123/dashboard/datatable.php",
      data: function (d) {
        d.datestart = datestart;
        d.dateend = dateend;
        window["lastpayload"] = d;
      },
    },
    columns: [
      {
        data: "Address",
        render: (id) => {
          const formattedAddress =
            id.substr(0, 9) + "..." + id.substr(id.length - 9);

          return `<span id="${id}" class="homeTableAddress" onclick="" >${formattedAddress}</span>`;
        },
      },
      { data: "TotalPNL" },
      { data: "RealizedPNL" },
      { data: "UnRealizedPNL" },
      { data: "TxCount" },
      {
        data: "Tags",
        render: function (e) {
          if (!e) {
            return null;
          }
          const arrayOfStrings = e.split(",");

          const labelElements = arrayOfStrings.map(
            (string, index) => `<div class="label" key=${index}>${string}</div>`
          );
          return labelElements.join("");
        },
      },
      {
        data: "Address",
        render: function (e) {
          return `
          <div class="eatherIconcontainer">
              <abbr title="link:0x89s">
                  <div
                  class="eatherIcon actionsIcons tradeAddressTab"
                  id="${e}"
                  width="20px"
                  height="20px"
                  type="button"
                  onclick="viewEather(${e})"
                  >
                  <img src="./images/ethereum.svg" alt="" />
                  </div>
              </abbr>
              <abbr title="View Trade">
                  <div class="eyesIcons actionsIcons u-btn"  type="button">
                  <img src="./images/view.png" alt="" />
                  </div>
              </abbr>
              <abbr title="View PNL">
                  <div class="eyesIcons actionsIcons earthIcon pnl-btn" width="20px" height="20px" type="button">
                  <img src="./images/stats.svg" alt="" />
                  </div>
              </abbr>
              <abbr title="Edit">
                  <div class="statisIcon actionsIcons earthIcon edit-modal" width="20px" height="20px" type="button" id="${e}">
                  <img src="./images/edit.png" alt="" />
                  </div>
              </abbr>
          </div> `;
        },
      },
    ],
    dom: "Blfrtip",
    buttons: [
      {
        extend: "csv",
        exportOptions: {
          columns: [0, 1, 2, 3, 4],
        },
        title: "CSV File",
      },
      "excel",
      "pdf",
    ],

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
      { targets: 0, orderable: false, searching: false, width: "10%" },
      { width: "10%", targets: 1 },
      { width: "10%", targets: 2 },
      { width: "10%", targets: 3 },
      { width: "10%", targets: 4 },
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

            if (dataRange[colIdx]) {
              $(cell).html(`<div class="mminput">
                              <input class="rangeinput" type="text" placeholder="${title}" />
                              <input class="mininput" type="text" placeholder="Min" />
                              <input class="maxinput" type="text" placeholder="Max" /></div>`);
            } else {
              $(cell).html('<input type="text" placeholder="' + title + '" />');
            }
          }
          // $('input',$('.filters th').eq($(api.column(colIdx).header()).index()))
          //     .off('keyup change')
          //     .on('change', function (e) {

          //         $(this).attr('title', $(this).val());
          //         var regexr = '({search})';
          //         var cursorPosition = this.selectionStart;
          //         api
          //             .column(colIdx)
          //             .search(this.value)
          //             .draw();
          //     })
          //     .on('keyup', function (e) {
          //         e.stopPropagation();
          //     });
        });
      // $.ajax({
      //   url: "http://144.76.163.123/dashboard/address.php?action=get_all_address_tags",
      //   method: "GET",
      //   success: function (data) {
      //     var options = "";
      //     data.forEach(function (option) {
      //       options += `<option value="${option}">${option}</option>`;
      //     });

      //     var target = $(table.column(5).header())
      //       .closest("thead")
      //       .find(".filters")
      //       .children(":nth-last-child(2)");

      //     target.empty();
      //     target.html(`
      //     <div class="custom-dropdown">
      //       <select class="searchFilterDropdown" id="addressTagDropDown" multiple="multiple">
      //         ${options}
      //       </select>
      //     </div>`);

      //     $("#addressTagDropDown").select2({
      //       placeholder: "Select",
      //       closeOnSelect: false,
      //       allowClear: true,
      //       dropdownCssClass: "bigdrop",
      //       multiple: true,
      //       width: "100%",
      //     });
      //   },
      //   error: function (xhr, status, error) {
      //     console.error(error);
      //   },
      // });
    },
  });

  $(".findThoDate").on("click", function (e) {
    const currentDate = new Date();
    let sixHoursBefore = new Date(currentDate);

    function setDuration(datePar) {
      const currentDate = new Date();
      const currentDay = currentDate.getDate();

      const sixDaysBefore = new Date(currentDate);
      sixDaysBefore.setDate(currentDay - datePar);

      const year = sixDaysBefore.getFullYear();
      const month = sixDaysBefore.getMonth() + 1;
      const day = sixDaysBefore.getDate();

      return `${year}-${month.toString().padStart(2, "0")}-${day.toString().padStart(2, "0")}`;
    }

    function getCurrentDateTime() {
      const currentDate = new Date();

      const year = currentDate.getFullYear();
      const month = (currentDate.getMonth() + 1).toString().padStart(2, "0");
      const day = currentDate.getDate().toString().padStart(2, "0");
      const hours = currentDate.getHours().toString().padStart(2, "0");
      const minutes = currentDate.getMinutes().toString().padStart(2, "0");
      const seconds = currentDate.getSeconds().toString().padStart(2, "0");

      const formattedDateTime = `${year}-${month}-${day}`;
      return formattedDateTime;
    }

    switch (e.currentTarget.id) {
      case "0":
        dateend = getCurrentDateTime();
        datestart = setDuration(1);
        break;
      case "1":
        dateend = getCurrentDateTime();
        datestart = setDuration(7);
        break;
      case "2":
        dateend = getCurrentDateTime();
        datestart = setDuration(30);
        break;

      default:
        dateend="";
        datestart=""
        null;
    }

    table.ajax.reload();
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

  $("body").on("input", ".mininput", function () {
    var maxData = $(this).closest(".mminput").find(".maxinput").val();
    var val = $(this).val() + ",," + maxData;
    if (val.length <= 2) {
      val = "";
    }
    $(this).closest(".mminput").find(".rangeinput").val(val);
  });

  $("body").on("input", ".maxinput", function () {
    var minData = $(this).closest(".mminput").find(".mininput").val();
    var val = minData + ",," + $(this).val();
    if (val.length <= 2) {
      val = "";
    }
    $(this).closest(".mminput").find(".rangeinput").val(val);
  });

  let table112 = null;
  let tablepnl = null;
  var tablehtml = $("#viewTrade-Modal .modal-content").html();
  var tablehtml_pnl = $("#viewTrade-Modal-pnl .modal-content").html();
  var editPopup_bach = $("#edit-trade-modal .modal-content").html();
  $("body").on("click", ".u-btn", function () {
    $("body").addClass("oflowhidden");

    var thSearchStatus = [
      false,
      true,
      true,
      true,
      false,
      false,
      false,
      false,
      false,
      false,
      false,
      false,
    ];

    if ($.fn.DataTable.isDataTable("#dt_mtable")) {
      if (table112 != null) {
        table112.clear().destroy();
        $("#viewTrade-Modal .modal-content").html(tablehtml);
        // $('#dt_mtable').dataTable().clear().destroy();
      }
    }

    $(".viewTrade-Modal, .modal-backdrop").addClass("show");
    var rowAddress = $(this).closest("tr").find("td span").attr("id");

    var address = rowAddress.match(/(0x[a-fA-F0-9]{40})/)[1];

    $(".address11").attr("href", "https://etherscan.io/address/" + address);
    $(".address11").text(address);

    $("#dt_mtable thead tr")
      .clone(true)
      .addClass("filter")
      .appendTo("#dt_mtable thead");

    table112 = $("#dt_mtable").DataTable({
      orderCellsTop: true,
      serverMethod: "post",
      scrollX: true,
      ajax: {
        url: "http://144.76.163.123/dashboard/popup.php",
        data: function (d) {
          d.address = address;
          d.datestart = datestart;
          d.dateend = dateend;
        },
      },
      // render: (id) => {
      //     false;
      //     const formattedAddress =
      //       id.substr(0, 9) + "..." + id.substr(id.length - 9);

      //     return `<span id="${id}" class="homeTableAddress" onclick="" >${formattedAddress}</span>`;
      //   },
      columns: [
        { defaultContent: address },
        { data: "TID" },
        {
          data: "FTOKEN",
          render: function (data) {
            const timestamp = new Date().getTime();
            var range = document.createRange();
            var fragment = range.createContextualFragment(data);
            var textData = fragment.firstElementChild.innerHTML;
            var linkdata = fragment.firstElementChild.getAttribute("href");
            var filtredStrings =
              textData.length > 30
                ? textData.substring(0, 20) +
                  "..." +
                  textData.substring(textData.length - 1)
                : textData;
            return `<div  class="tradePopUpFields">
              <span class='viewMoreContent' id='${
                textData + "*$" + timestamp
              }'>${filtredStrings}</span>
            <a href="${linkdata}" target="_blank" class="appendImage"><img src="./images/open.png" alt="Image Alt Text" class="openInnewTab"/></a>
            </div>`;
          },
        },
        {
          data: "TOTOKEN",
          render: function (data) {
            const timestamp = new Date().getTime();
            var range = document.createRange();
            var fragment = range.createContextualFragment(data);
            var textData = fragment.firstElementChild.innerHTML;
            var linkdata = fragment.firstElementChild.getAttribute("href");
            var filtredStrings =
              textData.length > 30
                ? textData.substring(0, 20) +
                  "..." +
                  textData.substring(textData.length - 1)
                : textData;
            return `<div  class="tradePopUpFields">
              <span class='viewMoreContent' id='${
                textData + "*$" + timestamp
              }'>${filtredStrings}</span>
            <a href="${linkdata}" target="_blank" class="appendImage"><img src="./images/open.png" alt="Image Alt Text" class="openInnewTab"/></a>
            </div>`;
          },
        },
        {
          data: "FAMOUNT",
          render: function (data) {
            return `<div style="max-width: 100px; word-wrap: break-word; overflow-wrap: break-word;"  >
              ${data}
              </div>`;
          },
        },
        {
          data: "TOAMOUNT",
          render: function (data) {
            return `<div style="max-width: 100px; word-wrap: break-word; overflow-wrap: break-word;" >
              ${data}
              </div>`;
          },
        },
        {
          data: "FAMOUNTUsd",
          render: function (data) {
            return `<div style="max-width: 100px; word-wrap: break-word; overflow-wrap: break-word;" >
              ${data}
              </div>`;
          },
        },
        {
          data: "TOAMOUNTUsd",
          render: function (data) {
            return `<div style="max-width: 100px; word-wrap: break-word; overflow-wrap: break-word;" >
              ${data}
              </div>`;
          },
        },
        {
          data: "PNLUSD",
          render: function (data) {
            return `<div style="max-width: 100px; word-wrap: break-word; overflow-wrap: break-word;">
              ${data}
              </div>`;
          },
        },
        {
          data: "PNLETH",
          render: function (data) {
            return `<div style="max-width: 100px; word-wrap: break-word; overflow-wrap: break-word;">
              ${data}
              </div>`;
          },
        },
        {
          data: "TDATEID",
          render: function (data) {
            return `<div style="max-width: 100px; word-wrap: break-word; overflow-wrap: break-word;">
              ${data}
              </div>`;
          },
        },
        {
          data: "PRICE",
          render: function (data) {
            return `<div style="max-width: 100px; word-wrap: break-word; overflow-wrap: break-word;">
              ${data}
              </div>`;
          },
        },
      ],

      // dom: 'Blfrtip',
      //     buttons: [
      //     'csv'
      // ],

      dom: "flBrtip",
      buttons: [
        {
          extend: "colvis",
          text: "Show/Hide Columns",
        },
        {
          extend: "csv",
          title: "Trade-" + address,
        },
      ],
      stateSave: true,
      stateSaveParams: function (settings, data) {
        data.search.search = "";
        data.columns.forEach((val) => {
          val.search.search = "";
        });
      },
      columnDefs: [
        { targets: 0, visible: false },
        { targets: 1, orderable: false, searching: false },
      ],
      language: {
        lengthMenu: "_MENU_",
        search: `<img src="./images/search.png" width="24" height="24" alt=""  /> _INPUT_`,
        // searchPlaceholder: "Search..."
      },

      lengthMenu: [
        [5, 10, 25, 50, 100, 500],
        [5, 10, 25, 50, 100, 500],
      ],
      pageLength: 10,
      initComplete: function () {
        var api = this.api();
        api
          .columns()
          .eq(0)
          .each(function (colIdx) {
            var cell = $(".filter th").eq(
              $(api.column(colIdx).header()).index()
            );
            var title = $(cell).text();

            if (thSearchStatus[colIdx]) {
              $(cell).html('<input type="text" placeholder="' + title + '" />');
            } else {
              $(cell).html("");
            }

            $(
              "input",
              $(".filter th").eq($(api.column(colIdx).header()).index())
            )
              .off("keyup change")
              .on("keyup change", function (e) {
                $(this).attr("title", $(this).val());
                var regexr = "({search})";
                var cursorPosition = this.selectionStart;

                api.column(colIdx).search(this.value).draw();
              })
              .on("keyup", function (e) {
                e.stopPropagation();
              });
          });
      },
      drawCallback: function () {
        var api = this.api();
        var sum = {};
        var formated = 0;

        $(api.column(0).footer()).html("Total");

        for (var i = 6; i <= 8; i++) {
          // if (i === 7) { continue; }

          if (sum[i] == undefined) sum[i] = 0;
          const apiData = api.column(i, { page: "current" }).data();

          for (var j = 0; j < apiData.length; j++) {
            var dt = apiData[j];
            if (dt) {
              dt = Number(dt.replace(/[$]/g, ""));
              if (!isNaN(dt)) {
                sum[i] += parseFloat(dt);
              }
            }
          }

          // formated = parseFloat(sum).toLocaleString(undefined, {minimumFractionDigits:2});
          let total = CICS(sum[i], i);
          console.log(total, "THIS IS TOTAL cic.--->");

          dsign = "";
          if (i !== 8) {
            dsign = "$";
          }
          $(api.column(i).footer()).html(
            total.indexOf("-") > -1
              ? "-" + dsign + total.substring(1, total.length)
              : dsign + total
          );
        }
      },
    });
  });
  $("#dt_table").on("click", ".tradeAddressTab", function (e) {
    window.open(`https://etherscan.io/address/${e.currentTarget.id}`, "_blank");
  });
  $("#dt_table").on("click", ".homeTableAddress", function (e) {
    e.currentTarget.innerHTML.toString().includes("...")
      ? (e.currentTarget.innerHTML = e.currentTarget.id)
      : (e.currentTarget.innerHTML =
          e.currentTarget.id.substr(0, 9) +
          "..." +
          e.currentTarget.id.substr(e.currentTarget.id.length - 9));
  });

  $("#dt_mtable").on("click", ".viewMoreContent", function (e) {
    if (e.currentTarget.innerHTML.length > 23) {
      let strIndex = e.currentTarget.id.indexOf("*$");
      let actutalString = e.currentTarget.id.slice(0, strIndex);

      e.currentTarget.innerHTML.toString().includes("...")
        ? (e.currentTarget.innerHTML = actutalString)
        : (e.currentTarget.innerHTML =
            e.currentTarget.innerHTML.substring(0, 20) +
            "..." +
            e.currentTarget.innerHTML.substring(
              e.currentTarget.innerHTML.length - 1
            ));
    }
  });
  // $('body').on('click', '.dt-button-collection .dt-button', function () {
  //     setTimeout(() => {
  //         $('#dt_mtable').DataTable().columns.adjust()
  //     }, 1000)

  // });

  $("body").on("click", ".pnl-btn", function () {
    var thSearchStatus = [
      false,
      true,
      false,
      false,
      false,
      false,
      false,
      false,
      true,
      false,
      false,
      false,
      false,
    ];

    if ($.fn.DataTable.isDataTable("#dt_mtable_pnl")) {
      if (tablepnl != null) {
        tablepnl.clear().destroy();
        $("#viewTrade-Modal-pnl .modal-content").html(tablehtml_pnl);
        // $('#dt_mtable_pnl').dataTable().clear().destroy();
      }
    }

    $("body").addClass("oflowhidden");
    $(".viewTrade-Modal-pnl").toggleClass("show");

    var rowAddress = $(this).closest("tr").find("td span").attr("id");
    var address = rowAddress.match(/(0x[a-fA-F0-9]{40})/)[1];

    $(".address11").attr("href", "https://etherscan.io/address/" + address);
    $(".address11").text(address);

    $("#dt_mtable_pnl thead tr")
      .clone(true)
      .addClass("filter1")
      .appendTo("#dt_mtable_pnl thead");

    tablepnl = $("#dt_mtable_pnl").DataTable({
      orderCellsTop: true,
      serverMethod: "post",
      scrollX: true,
      autoWidth: false,
      ajax: {
        url: "http://144.76.163.123/dashboard/pnl.php",
        data: function (d) {
          d.address = address;
          d.datestart = datestart;
          d.dateend = dateend;
        },
      },
      columns: [
        { defaultContent: address },
        {
          data: "Token",
          render: (data) => {
            return `<div class="tradePanelPopUp">${data}</div>`;
          },
        },
        {
          data: "Bought",
          render: (data) => {
            return `<div class="tradePopUpFields">${data}</div>`;
          },
        },
        {
          data: "Sold",
          render: (data) => {
            return `<div class="tradePopUpFields">${data}</div>`;
          },
        },
        {
          data: "UnSold",
          render: (data) => {
            return `<div class="tradePopUpFields">${data}</div>`;
          },
        },
        {
          data: "RealizedPNL",
          render: (data) => {
            return `<div class="tradePopUpFields">${data}</div>`;
          },
        },
        {
          data: "UnRealizedPNL",
          render: (data) => {
            return `<div class="tradePopUpFields">${data}</div>`;
          },
        },
        {
          data: "TotalPNL",
          render: (data) => {
            return `<div class="tradePopUpFields">${data}</div>`;
          },
        },
        // { data: "TransfersPNL" },
        {
          data: "TradeCount",
          render: (data) => {
            return `<div style="max-width: 60px; word-wrap: break-word; overflow-wrap: break-word;">${data}</div>`;
          },
        },
        {
          data: "Status",
          render: (data) => {
            return `<div style="max-width: 60px; word-wrap: break-word; overflow-wrap: break-word;">${data}</div>`;
          },
        },
        {
          data: "Entry",
          render: (data) => {
            return `<div style="max-width: 60px; word-wrap: break-word; overflow-wrap: break-word;">${data}</div>`;
          },
        },
        {
          data: "Exit",
          render: (data) => {
            return `<div style="max-width: 60px; word-wrap: break-word; overflow-wrap: break-word;">${data}</div>`;
          },
        },
        {
          data: "StartDate",
          render: (data) => {
            return `<div style="max-width: 60px; word-wrap: break-word; overflow-wrap: break-word;">${data}</div>`;
          },
        },
        {
          data: "EndDate",
          render: (data) => {
            return `<div style="max-width: 60px; word-wrap: break-word; overflow-wrap: break-word;">${data}</div>`;
          },
        },
      ],
      dom: "Blfrtip",
      buttons: [
        {
          extend: "csv",
          title: "PNL-" + address,
        },
      ],
      stateSave: true,
      stateSaveParams: function (settings, data) {
        data.search.search = "";
        data.columns.forEach((val) => {
          val.search.search = "";
        });
      },
      columnDefs: [
        { targets: 0, visible: false },
        { targets: 1, orderable: false, searching: false },
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
        api
          .columns()
          .eq(0)
          .each(function (colIdx) {
            var cell = $(".filter1 th").eq(
              $(api.column(colIdx).header()).index()
            );
            var title = $(cell).text();

            if (thSearchStatus[colIdx]) {
              $(cell).html('<input type="text" placeholder="' + title + '" />');
            } else {
              $(cell).html("");
            }

            $(
              "input",
              $(".filter1 th").eq($(api.column(colIdx).header()).index())
            )
              .off("keyup change")
              .on("keyup change", function (e) {
                $(this).attr("title", $(this).val());
                var regexr = "({search})";
                var cursorPosition = this.selectionStart;
                api.column(colIdx).search(this.value).draw();
              })
              .on("keyup", function (e) {
                e.stopPropagation();
              });
          });

        $("#dt_mtable_pnl_wrapper .sorting:eq(0)").trigger("click");
      },
      drawCallback: function () {
        var api = this.api();
        var sum = 0;
        var formated = 0;

        $(api.column(0).footer()).html("Total");

        for (var i = 2; i <= 8; i++) {
          // if (i === 7) { continue; }
          let sum = api.column(i, { page: "current" }).data().sum();
          if (isNaN(sum))
            sum = api
              .column(i, { page: "current" })
              .data()
              .map((a) => $(a).text())
              .sum();
          dsign = "";
          if (i !== 8) {
            dsign = "$";
          }
          $(api.column(i).footer()).html(
            dsign +
              parseFloat(sum).toLocaleString(undefined, {
                minimumFractionDigits: 2,
              })
          );
        }
      },
    });
  });

  // This is Edit table

  $("body").on("click", ".edit-modal", function (e) {
    $("#edit-trade-modal .modal-content").html(editPopup_bach);
    $("body").addClass("oflowhidden");
    $(".edit-trade-modal").toggleClass("show");
    $(".js-example-tags").select2({
      tags: true,
    });
    $(".tagAddressHome").html(e.currentTarget.id);

    $(".updatedStatus").attr("id", e.currentTarget.id);
    $(".js-example-basic-single").select2({
      minimumResultsForSearch: Infinity,
    });
  });
});

$("body").on("click keyup", function (e) {
  if (e.keyCode == 27 || e.target == $(".modal.show")[0]) {
    $(".modal.show").removeClass("show");
    $("body").removeClass("oflowhidden");
  }
});

function CICS(labelValue, i) {
  // Nine Zeroes for Billions
  console.log(labelValue, i, "labelValue----->");
  if (i == 5)
    console.log(
      "function ",
      Number(labelValue),
      (Number(labelValue) / 1.0e3).toFixed(2)
    );

  if (Number(labelValue) >= 1e12) {
    return (Number(labelValue) / 1e12).toFixed(2) + "<strong>T</strong>";
  }
  return Number(labelValue) >= 1.0e9
    ? (Number(labelValue) / 1.0e9).toFixed(2) + "<strong>B</strong>"
    : // Six Zeroes for Millions
    Number(labelValue) >= 1.0e6
    ? (Number(labelValue) / 1.0e6).toFixed(2) + "<strong>M</strong>"
    : // Three Zeroes for Thousands
    Number(labelValue) >= 1.0e3
    ? (Number(labelValue) / 1.0e3).toFixed(2) + "<strong>K</strong>"
    : Number(labelValue).toFixed(2);
}

$("body").on("click", ".viewTrade-Modal .cross-svg", function () {
  $("body").removeClass("oflowhidden");
  $(".viewTrade-Modal, .modal-backdrop").removeClass("show");
});
$("body").on("click", ".viewTrade-Modal-pnl .cross-svg", function () {
  $("body").removeClass("oflowhidden");
  $(".viewTrade-Modal-pnl").removeClass("show");
});

$("body").on("click", ".edit-trade-modal .cross-svg", function () {
  $("body").removeClass("oflowhidden");
  $(".edit-trade-modal").removeClass("show");
});
// http://144.76.163.123/dashboard/
//

var hostUrl = "http://144.76.163.123/"; //window.location.protocol + "//" + window.location.hostname;
$("#export-all").on("click", function () {
  // window.open(hostUrl + "/data1/datatable.php?exportcsv=1&data=" + encodeURIComponent(JSON.stringify(window.lastpayload)));
  window.open(
    hostUrl +
      "dashboard/datatable.php?exportcsv=1&data=" +
      encodeURIComponent(JSON.stringify(window.lastpayload))
  );
});
$("#pnl-by-token").on("click", function () {
  const inputval = $("#pnl-input-address").val();
  // window.open(hostUrl + "/data1/pnlbytoken1.php?address="+inputval);
  window.open(hostUrl + "/dashboard/pnlbytoken1.php?address=" + inputval);
});

function copyToClipboard(text) {
  if (window.clipboardData && window.clipboardData.setData) {
    // Internet Explorer-specific code path to prevent textarea being shown while dialog is visible.
    return window.clipboardData.setData("Text", text);
  } else if (
    document.queryCommandSupported &&
    document.queryCommandSupported("copy")
  ) {
    var textarea = document.createElement("textarea");
    textarea.textContent = text;
    textarea.style.position = "fixed"; // Prevent scrolling to bottom of page in Microsoft Edge.
    document.body.appendChild(textarea);
    textarea.select();
    try {
      return document.execCommand("copy"); // Security exception may be thrown by some browsers.
    } catch (ex) {
      console.warn("Copy to clipboard failed.", ex);
      return prompt("Copy to clipboard: Ctrl+C, Enter", text);
    } finally {
      document.body.removeChild(textarea);
    }
  }
}

var rowAddressTags = null;
$("body").on("click", ".edit-modal", function (e) {
  rowAddressTags = e.currentTarget.id;

  $.ajax({
    url:
      "http://144.76.163.123/dashboard/address.php?action=get_address_tags&address_id=" +
      rowAddressTags,
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
      alert("Something Went Wrong");
    },
  });
  $.ajax({
    url: "http://144.76.163.123/dashboard/address.php?action=get_all_address_tags",
    method: "GET",
    dataType: "json",
    success: function (data) {
      var select = $("#mySelect");
      select.empty();
      setTimeout(() => {
        var availableOptions = [];

        select.find("option").each(function () {
          availableOptions.push($(this).val());
        });

        data.forEach(function (option) {
          if (!availableOptions.includes(option)) {
            select.append(new Option(option, option, false, false));
          }
        });

        select.select2({
          tags: true,
        });
      }, 1000);
    },
    error: function (xhr, status, error) {
      alert("Something Went Wrong");
    },
  });
});

$("body").on("click", ".addTag", function () {
  var selectedTags = $("#mySelect").val();
  var jsonData = JSON.stringify(selectedTags);
  $.ajax({
    url:
      "http://144.76.163.123/dashboard/address.php?action=update_address_tags&address_id=" +
      rowAddressTags,
    method: "POST",

    data: $.param({ tags: jsonData }),
    dataType: "json",
    success: function (data) {
      alert("successful");
    },
    error: function (xhr, status, error) {
      alert("Something Went Wrong");
    },
  });
});
