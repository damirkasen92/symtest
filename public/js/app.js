$(function () {
    'use strict';

    // i was not said that how to write code in js. So )))

    setButtonHandle('delete');
    setButtonHandle('block');
    setButtonHandle('unblock');

    function setButtonHandle(action) {
        let $delBtn = $('[data-button-action="' + action + '"]');

        $delBtn.on('click', function () {
            const userIds = $('[name="user_id"]:checked')
                .map((_, el) => el.value).get();

            if (!userIds) return;

            $.post('/user/' + action, {
                userIds,
            }, (response) => {
                if (response.status === 'ok')
                    window.location.reload();
            });
        });
    }

    (function () {
        let $delUnverifiedBtn = $('[data-button-action="delete-unverified"]');

        $delUnverifiedBtn.on('click', function () {
            $('[data-is-unverified="0"]').prop('check', false);
            $('[data-is-unverified="1"]').prop('check', true);
            $('[data-button-action="delete"]').trigger('click');
        });

        // data-is-unverified
    })();

    (function () {
        let $toggleAllBtn = $('#selectAll');

        $toggleAllBtn.on('click', function () {
            let $this = $(this);
            let isChecked = $this.prop('checked');
            let $allCheckboxes = $this.closest('table').find('[name="user_id"]');

            $allCheckboxes.prop('checked', isChecked);
        });

        let $toggleBtns = $('[name="user_id"]');

        $toggleBtns.on('click', function () {
            let $checkedBtns = $('[name="user_id"]:checked');

            if ($checkedBtns.length === $toggleBtns.length) {
                $toggleAllBtn.prop('checked', true);
                return;
            }

            $toggleAllBtn.prop('checked', false);
        });

    })();
});