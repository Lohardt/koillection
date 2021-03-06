import Translator from './translator.min.js'
import * as utils from './utils'
import * as select from './select'
import Cookies from '../node_modules/js-cookie'

$(document).ready(function() {
    window.addEventListener('online', handleConnectionChange);
    window.addEventListener('offline', handleConnectionChange);

    $('.burger-menu').click(function (e) {
        e.preventDefault();
        var open = !$('body').hasClass('mobile-opened');
        if (open) {
            $('body').addClass('mobile-opened');
        } else {
            $('body').removeClass('mobile-opened');
        }
    });

    $('.mobile-overlay').click(function (e) {
        e.preventDefault();
        $('body').removeClass('mobile-opened');
    });

    $('.header-profile').click(function (e) {
        e.preventDefault();
        $('.profile-menu').removeClass('hidden');

        $(document).mouseup(function(e) {
            var $container = $('.profile-menu');
            if (!$container.is(e.target) && $container.has(e.target).length === 0) {
                $container.addClass('hidden');
                $(document).unbind('mouseup')
            }
        });
    });

    select.loadSelect2();
    select.loadSelect2Countries();
    select.loadSelect2Locales();
    select.loadSelect2TagCategories();

    M.Timepicker.init(document.querySelectorAll('.timepicker'), {
        showClearBtn: true,
        twelveHour: false
    });
    M.Dropdown.init(document.querySelectorAll('.dropdown-trigger'));
    M.Modal.init(document.querySelectorAll('.modal'));
    M.Tooltip.init(document.querySelectorAll('.tooltipped'), {
        enterDelay: 500,
        outDuration: 100
    });
    M.Collapsible.init(document.querySelectorAll('.collapsible'));



    //Init MaterializeCSS datepickers
    M.Datepicker.init(document.querySelectorAll('.datepicker'), {
        months: [Translator.trans('global.months.january'), Translator.trans('global.months.february'),
            Translator.trans('global.months.march'), Translator.trans('global.months.april'), Translator.trans('global.months.may'),
            Translator.trans('global.months.june'), Translator.trans('global.months.july'), Translator.trans('global.months.august'),
            Translator.trans('global.months.september'), Translator.trans('global.months.october'), Translator.trans('global.months.november'), Translator.trans('global.months.december')],
        monthsShort: [Translator.trans('global.months.january').substring(0, 3), Translator.trans('global.months.february').substring(0, 3),
            Translator.trans('global.months.march').substring(0, 3), Translator.trans('global.months.april').substring(0, 3), Translator.trans('global.months.may').substring(0, 3),
            Translator.trans('global.months.june').substring(0, 3), Translator.trans('global.months.july').substring(0, 3), Translator.trans('global.months.august').substring(0, 3),
            Translator.trans('global.months.september').substring(0, 3), Translator.trans('global.months.october').substring(0, 3), Translator.trans('global.months.november').substring(0, 3),
            Translator.trans('global.months.december').substring(0, 3)],
        weekdays: [Translator.trans('global.days.sunday'), Translator.trans('global.days.monday'), Translator.trans('global.days.tuesday'), Translator.trans('global.days.wednesday'),
            Translator.trans('global.days.thursday'), Translator.trans('global.days.friday'), Translator.trans('global.days.saturday')],
        weekdaysAbbrev: [Translator.trans('global.days.sunday').substring(0, 1), Translator.trans('global.days.monday').substring(0, 1), Translator.trans('global.days.tuesday').substring(0, 1),
            Translator.trans('global.days.wednesday').substring(0, 1), Translator.trans('global.days.thursday').substring(0, 1), Translator.trans('global.days.friday').substring(0, 1),
            Translator.trans('global.days.saturday').substring(0, 1)],
        clear: Translator.trans('btn.clear'),
        close: Translator.trans('btn.close'),
        today: Translator.trans('global.today').substring(0, 3)+'.',
        format: $('#js-date-format').data('jsDateFormat')
    });

    $('form input').keydown(function (e) {
        if (e.keyCode == 13 && !$(this).closest('form').hasClass('login') && !$(this).closest('form').hasClass('search')) {
            e.preventDefault();
            return false;
        }
    });

    //Init tabs
    $('.tab').click(function () {
        $('.tab').removeClass('current');
        $(this).addClass('current');
        $('.panel').addClass('hidden');
        $('#' + $(this).attr('for')).removeClass('hidden');
    });

    //Init lightboxes
    if ($('[name="data-lightbox"]').length > 0) {
        lightbox.option({
            'resizeDuration': 200,
            'imageFadeDuration': 200,
            'fadeDuration': 200,
            'wrapAround': true
        });
    }

    //init image slider
    $('.slider-element').click(function (e) {
        e.preventDefault();
        var $imageFrame = $(this).closest('.slider-container').find('.slider-frame:first');
        console.log($(this).find('img').attr('data-image'));

        $imageFrame.find('a:first').attr('href', $(this).attr('href'));
        $imageFrame.find('a:first').attr('data-title', $(this).attr('data-title'));
        $imageFrame.find('img:first').attr('src', $(this).find('img').attr('data-image'));
        $imageFrame.find('.image-label:first').html($(this).attr('data-title'));
        $(this).closest('.slider-elements').find('.slider-element').removeClass('active');
        $(this).addClass('active');
    });

    utils.loadFilePreviews();


    //TEMPLATE FIELDS
    var $collectionHolder = $('#template-fields-holder');
    //Init sortable
    if ($('#template-fields-holder').find('.field').length > 0) {
        utils.reloadSortableList($collectionHolder, '.field');
    }

    var indexFieldTemplate = $collectionHolder.find('.field').length;
    var $addFieldLink = $('<a href="#" class="add_field_link waves-effect waves-light btn">Add a new field</a>');
    var $newLinkDiv = $('<div></div>').append($addFieldLink);
    $collectionHolder.append($newLinkDiv);
    utils.computePositions($collectionHolder);

    $addFieldLink.on('click', function(e) {
        e.preventDefault();
        addFieldForm($collectionHolder, $newLinkDiv);
    });

    function addFieldForm($collectionHolder, $newLinkDiv) {
        var prototype = $collectionHolder.attr('data-prototype');
        var $newForm = $(prototype.replace(/__name__/g, indexFieldTemplate));
        $newForm.find('.removeDatum').on('click', function(e) {
            e.preventDefault();
            $newForm.remove();
        });
        $newLinkDiv.before($newForm);
        console.log($newForm);
        indexFieldTemplate++;
        select.loadSelect2();
        utils.computePositions($collectionHolder);
        utils.reloadSortableList($collectionHolder, '.field');
    }

    $('#template-fields-holder').on( "click", ".removeDatum", function() {
        $(this).closest('.field').remove();
        utils.computePositions($collectionHolder);
    });

    //Search autocomplete
    $('#search_term').click(function (e) {
        if ($(this).val().length > 2) {
            $('.autocomplete-results').show();
        }
    });

    $('#search_term').keyup(function (e) {
        let $this = $(this);
        delay(function() {
            let value = $this.val();
            let $autcompleteResultsWrapper = $('.autocomplete-results');
            if (value.length < 2) {
                $autcompleteResultsWrapper.hide();
                return;
            }

            $.get('/search/autocomplete/' + encodeURIComponent($this.val()), function( data ) {
                $autcompleteResultsWrapper.show();
                $autcompleteResultsWrapper.html('');
                $.each( data.results, function( key, result ) {
                    $autcompleteResultsWrapper.append(autocompleteResultFactory(highlight(result.label, value), result.url, result.type));
                });

                if (data.totalResultsCounter > 5) {
                    let url = "/search?search[term]=" + value + "&search[searchInCollections]=&search[searchInItems]=&search[searchInTags]=";
                    let label = Translator.transChoice('global.search.more_results', data.totalResultsCounter - 5);
                    $autcompleteResultsWrapper.append(autocompleteResultFactory(label, url));
                }

                if (data.totalResultsCounter == 0) {
                    let url = null;
                    let label = Translator.transChoice('global.search.no_results');
                    $autcompleteResultsWrapper.append(autocompleteResultFactory(label, url));
                }
            });
        }, 250 );
    });

    $(document).mouseup(function(e)
    {
        let container = $(".autocomplete-results");

        // if the target of the click isn't the container nor a descendant of the container
        if (!container.is(e.target) && container.has(e.target).length === 0)
        {
            container.hide();
        }
    });

    function autocompleteResultFactory(label, url, type = null)
    {
        let $li = $('<li class="autocomplete-result"></li>');

        if (url) {
            let $a = $('<a></a>').attr('href', url);
            $a.append(label)
            $li.append($a);

            if (type) {
                let $type = $('<span></span>').append(' (' + type + ')')
                $a.append($type);
            }
        } else {
            $li.append(label);
        }

        return $li;
    }

    function highlight(content, terms) {
        terms = terms.split(' ');
        $.each(terms, function( key, term ) {
            let search = term.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')
            search += '(?![^<]*>)'; //Prevent matchig 'b' character inside the <b> tag
            let regex = new RegExp(search,'i');
            content = content.replace(regex, `<b>$&</b>`);
        });

        return content;
    }

    let delay = (function(){
        let timer = 0;
        return function(callback, ms){
            clearTimeout (timer);
            timer = setTimeout(callback, ms);
        };
    })();
});

function handleConnectionChange(event){
    var element = document.getElementById("offline-message");
    if(event.type == "offline"){
        element.classList.remove("hidden");
    }

    if(event.type == "online"){
        element.classList.add("hidden");
    }
}
