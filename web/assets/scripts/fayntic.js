var fayntic = {
    dispatch: function () {
        fayntic.bindCompletionSelectors(jQuery('.typeahead'));
        fayntic.bindTableFormSelectors(jQuery('table *[data-action]'));
        fayntic.bindTablesorterSelectors(jQuery('table.table-sortable'));
        fayntic.bindValidateFormSelectors(jQuery('*[data-validate-url]'));

        jQuery('a.btn[title]').tooltip({container: 'body'});
    },

    bindCompletionSelectors: function (completionSelectors) {
        var directories = new Bloodhound({
            datumTokenizer: function (d) {
                return Bloodhound.tokenizers.whitespace(d.value);
            },
            queryTokenizer: Bloodhound.tokenizers.whitespace,
            limit: 10,
            remote: '/account/product/ftp/directories/?path=%QUERY'
        });

        directories.initialize();

        completionSelectors.typeahead(null, {
            highlight: true,
            autoselect: true,
            source: directories.ttAdapter()
        });
    },

    bindTableFormSelectors: function (tableFormSelectors) {
        tableFormSelectors.on('click', function (event) {
            event.preventDefault();
            var action = jQuery(this).data('action');
            var tableRow = jQuery(this).closest('tr');

            if ('create' === action) {
                fayntic.createTableFormEntry(tableRow);
            } else {
                fayntic.deleteTableFormEntry(tableRow);
            }
        });
    },
    createTableFormEntry: function (templateRecord) {
        var lastDataRecord = templateRecord.prev('tr[data-type="data"]');
        var index = lastDataRecord ? lastDataRecord.data('index') + 1 : 0;

        var dataRecord = templateRecord;
        templateRecord = dataRecord.clone(true);

        dataRecord.attr('data-type', 'data');
        dataRecord.attr('data-index', index);

        dataRecord.find('*[data-action]').removeClass('btn-success').addClass('btn-danger').data('action', 'delete');
        dataRecord.find('*[data-action] > *[class^=icon-]').removeClass('icon-plus-circle').addClass('icon-minus-circle');

        jQuery.each(dataRecord.find('*[data-name]'), function (formIndex, formNode) {
            var formElement = jQuery(formNode);
            formElement.attr('name', formElement.data('name').replace('%', index));
        });

        jQuery.each(templateRecord.find('input, select'), function (formIndex, formNode) {
            var formElement = jQuery(formNode);
            formElement.val(formElement.data('value'));
        });

        dataRecord.after(templateRecord);
    },
    deleteTableFormEntry: function (dataRecord) {
        var confirmation = dataRecord.find('*[data-action]').data('confirm');
        if (!confirmation || confirm(confirmation)) {
            dataRecord.remove();
        }
    },

    bindValidateFormSelectors: function (validateSelectors) {
        jQuery('form').on('submit', function () {
            var formElement = jQuery(this);
            var submitButtons = formElement.find('*[type="submit"]');
            submitButtons.prop('disabled', true);
            submitButtons.find('span').removeClass('icon-disk').addClass('icon-spinner3');
        });

        jQuery.each(validateSelectors, function (validateIndex, validateNode) {
            if (jQuery(validateNode).val()) {
                fayntic.validateFormInput(jQuery(validateNode));
            }
        });

        validateSelectors.bindWithDelay('keyup change', function () {
            fayntic.validateFormInput(jQuery(this));
        }, 200);
    },
    validateFormInput: function (validateElement) {
        var data = {};
        data.value = validateElement.val();

        jQuery.each(validateElement.data(), function (dataName, dataValue) {
            if (dataName.match(/^validateData/)) {
                dataName = dataName.replace('validateData', '');
                eval('data.'+dataName+' = dataValue');
            }
        });

        // todo obsolete, use *[data-validate-data-*="value"]
        var comparisonId = validateElement.data('comparison');
        if (comparisonId) {
            data.comparison = jQuery('input#' + comparisonId).val();
        }

        jQuery.ajax({
            url: validateElement.data('validate-url'),
            method: 'post',
            data: data
        }).success(function (response) {
            var formGroupElement = validateElement.closest('div.form-group');
            var formFeedbackElement = formGroupElement.find('span.form-control-feedback');
            var formHelpElement = formGroupElement.find('.help-block');
            var formElement = formGroupElement.closest('form');

            formGroupElement.removeClass('has-success has-error').addClass('has-feedback');
            formFeedbackElement.removeClass('icon-checkmark-circle2 icon-cancel-circle2');

            if (response) {
                formHelpElement.removeClass('hide').text(response).fadeIn();
                formGroupElement.addClass('has-error');
                if ('SELECT' !== validateElement.prop('tagName')) {
                    formFeedbackElement.addClass('icon-cancel-circle2');
                }
            } else {
                formHelpElement.hide();
                formGroupElement.addClass('has-success');
                formGroupElement.closest('.form-control-feedback');
                if ('SELECT' !== validateElement.prop('tagName')) {
                    formFeedbackElement.addClass('icon-checkmark-circle2');
                }
            }
            if (formElement.find('.has-error').length) {
                formElement.find('*[type="submit"]').prop('disabled', true);
            } else {
                formElement.find('*[type="submit"]').prop('disabled', false);
            }
        });
    },

    bindTablesorterSelectors: function (tableSelectors) {
        jQuery.extend(jQuery.tablesorter.themes.bootstrap, {
            table: 'table',
            caption: 'caption',
            header: 'bootstrap-header',
            footerRow: '',
            footerCells: '',
            icons: '',
            sortNone: 'icon-xs icon-arrow4',
            sortAsc: 'icon-xs icon-arrow-up4',
            sortDesc: 'icon-xs icon-arrow-down4',
            active: '',
            hover: '',
            filterRow: '',
            even: '',
            odd: ''
        });

        tableSelectors.tablesorter({
            theme: "bootstrap",
            widthFixed: true,
            sortMultiSortKey: 'shiftKey',
            headerTemplate: '{content} {icon}',
            widgets: [ 'uitheme', 'filter', 'zebra' ],
            widgetOptions: {
                zebra: ["even", "odd"],
                filter_cssFilter: 'form-control input-sm',
                filter_reset: ".reset"
            }
        }).tablesorterPager({
            container: jQuery('ul.pagination'),
            cssGoto: ".pagenum",
            removeRows: false,
            cssDisabled: 'disabled',
            output: '{startRow} - {endRow} / {filteredRows} ({totalRows})'
        });
    }
};
jQuery(document).ready(jQuery.proxy(fayntic.dispatch, fayntic));
