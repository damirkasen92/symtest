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