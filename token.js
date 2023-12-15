$(document).ready(function () {
  $(".toggle-menu").click(function () {
    $(".sidebar").toggleClass("active");
  });
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
      { data: "Address" },
      { data: "IsMainToken" },
      { data: "IsScamToken" },
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
        data: "Id",
        render: function (e) {
          return `
          
        <div class="eatherIconcontainer">
            <abbr title="View PNL">
                <div class="statisIcon actionsIcons erthIcon elditTokenPageMainleft edit-modal edit-icon-div" width="20px" height="20px" type="button" id="${e}" >
                <img src="./edit.png" alt="" />
                </div>
            </abbr>
            <abbr title="View PNL">
                <div class="statisIcon actionsIcons elditTokenPageMain erthIcon  edit-icon-div" width="20px" height="20px" type="button" id="${e}" >
                <img src="./viewM.png" alt="" />
                </div>
            </abbr>
        </div> 
        
        `;
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
      { targets: 0, orderable: true, searching: false, width: "10%" },
      { width: "10%", targets: 1, searching: false },
      { width: "10%", targets: 2, searching: false },
      { width: "10%", targets: 3, orderable: false, searching: false },
      { width: "10%", targets: 4, searching: false },
      { width: "10%", targets: 5, orderable: false, searching: false },
      { width: "10%", targets: 6, orderable: false, searching: true },
      { width: "10%", targets: 7, orderable: false, searching: true },
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
          if (colIdx != 7) {
            var cell = $(".filters th").eq(
              $(api.column(colIdx).header()).index()
            );
            var title = $(cell).text();
            $(cell).html('<input type="text" placeholder="' + title + '" />');
          }
        });
    },
  });

  table.buttons(".buttons-copy").remove();
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
  var requestObject = {};
  $("body").on("click", ".edit-modal", function (e) {
    $.ajax({
      url:
        "http://144.76.163.123/dashboard/tokens.php?action=get_token_detail&token_id=" +
        e.currentTarget.id,
      method: "GET",
      dataType: "json",
      success: function (data) {
        requestObject = data;
      },
      error: function (xhr, status, error) {
        alert("Something Went Wrong");
      },
    });
    var id = $(this).closest("tr").find("td:first").text();

    $("#edit-trade-modal .modal-content").html(editPopup_bach);
    $("body").addClass("oflowhidden");
    $(".edit-trade-modal").toggleClass("show");
    $(".js-example-tags").select2({
      tags: true,
    });

    $(".js-example-basic-single").select2({
      minimumResultsForSearch: Infinity,
    });

    $(".popup-tokenId").html(e.currentTarget.id);
    $(".updatedStatus").attr("id", e.currentTarget.id);

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
        alert("Something Went Wrong");
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
          alert("successful");
        },
        error: function (xhr, status, error) {
          alert("Something Went Wrong");
        },
      });
    });
  });

  $("body").on("click", ".elditTokenPageMain", function (e) {
    var table = $("#token_table").DataTable();

    $("#token_table tbody").on("click", "tr", function () {
      var rowData = table.row(this).data();
      if (rowData) {
        var addressHTML = rowData.Address;
        var parser = new DOMParser();
        var doc = parser.parseFromString(addressHTML, "text/html");
        var aTag = doc.querySelector("a");

        if (aTag) {
          var href = aTag.getAttribute("href");
          var hexCode = href.split("/").pop();

          window.open(
            "http://144.76.163.123/dashboard/tokeninfo.php?tk=" + hexCode,
            "_blank"
          );
        }
      }
    });
  });

  $("body").on("click", ".updatedStatus", function (e) {
    var mainTokenValue = $(".main-token");
    if (mainTokenValue.val()) {
      requestObject[mainTokenValue.attr("id")] = mainTokenValue.val();
    }
    var scamTokenValue = $(".scam-token");
    if (scamTokenValue.val()) {
      requestObject[scamTokenValue.attr("id")] = scamTokenValue.val();
    }

    if (
      requestObject.hasOwnProperty("IsScamToken") ||
      requestObject.hasOwnProperty("IsMainToken")
    ) {
      $.ajax({
        url:
          "http://144.76.163.123/dashboard/tokens.php?action=update_token&token_id=" +
          e.currentTarget.id,
        method: "POST",
        // data: JSON.stringify(requestObject),
        data: requestObject,
        success: function (response) {
          // Handle success response
          console.log("Data updated successfully");
        },
        error: function (xhr, status, error) {
          // Handle error response
          console.error("Error updating data:", error);
        },
      });
    }
    console.log(requestObject, "requestObject->>");
  });

  $("body").on("click", ".edit-trade-modal .remove-modal", function (e) {
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
