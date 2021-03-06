# click to make input
jQuery ($)->
    # utilities
    ## event definition for each element
    refreshEvents = ->
        $('.click2input, .click2inputs')
            .off 'click'
            .on 'click', ->
                $(this)
                    .hide()
                    .next()
                    .show()
                    .focus()
                    .select()

        $('.clicked2input')
            .off 'focusout'
            .on 'focusout', ->
                id = $(this).parent().parent().data 'id'
                updates = {}
                updates[$(this).data 'updatable'] = $(this).val()
                $.post ajax.Endpoints, {
                    action: 'category_synonyms_update_def'
                    id
                    updates
                }
                $(this)
                    .hide()
                    .prev()
                    .text $(this).val()
                    .show()


        $('.clicked2inputs')
            .off 'focusout'
            .on 'focusout', ->
                id = $(this).parent().parent().data 'id'
                terms = $(this).val().split(',')
                    .filter((v)->return v isnt '')
                updates = {}
                updates[$(this).data 'updatable'] = terms
                console.log {
                    action: 'category_synonyms_update_def'
                    id
                    updates
                }
                $.post ajax.Endpoints, {
                    action: 'category_synonyms_update_def'
                    id
                    updates
                }
                $(this)
                    .prev()
                    .children()
                    .remove()
                if terms.length is 0
                    $(this).prev().append '<li>+</li>'
                else
                    $(this).prev().append(("<li>#{term}</li>" for term in terms).join(''))
                $(this)
                    .hide()
                    .prev()
                    .show()


    getCheckedIDs = ->
        result = []
        $('input:checked').each ->
            id = $(this).attr('id')
            if /cb-select-[1-9][0-9]*/.test id
                result.push $(this).val()
        return result


    # ajax entries for all
    refreshEvents()

    $('input#doaction').on 'click', ->
        # delete def(s)
        if $('#bulk-action-selector-top').val() is 'delete'
            def2del = getCheckedIDs()
            if def2del.length > 0
                $.post ajax.Endpoints, {
                    action: 'category_syonyms_delete_defs'
                    ids: def2del
                }
                , (res)->
                    for id, success of res.data
                        if success
                            $('tr#synonyms-' + id).remove()

    # add a new def
    $('.click2add.page-title-action').on 'click', ->
        $.post ajax.Endpoints, {
            action:'category_synonyms_add_new'
        }
        , (res)->
            $(res).prependTo $('table.category-synonyms-ui>tbody')
            refreshEvents()
