jQuery(function ($) {
    window.DWEB_PS_ADMIN = [];
    window.DWEB_PS_ADMIN.sync = function (endPoint, values, method) {
        if (typeof dwebPsAdmin === 'undefined') {
            return Promise.reject('DWEB_PS_ADMIN: dwebPsAdmin is not defined');
        }
        method = method || 'post';
        const defaultValues = {
            _ajax_nonce: dwebPsAdmin['security'],
            action: endPoint,
        }
        return new Promise((resolve, reject) => {
            if(method === 'post') {
                $.post(dwebPsAdmin['ajaxUrl'], {...defaultValues, ...values}, function (response) {
                    if (response['success'] === true) {
                        resolve(response)
                    } else {
                        reject(response)
                    }
                }, "json");
            }
            else{
                $.get(dwebPsAdmin['ajaxUrl'], {...defaultValues, ...values}, function (response) {
                    if (response['success'] === true) {
                        resolve(response)
                    } else {
                        reject(response)
                    }
                }, "json");
            }
        })
    }

    $('body').on('click', '#dweb_ps-save-settings', function (e) {
        e.preventDefault();
        const $button = $(e.currentTarget);
        const defaultLabel = $button.text().trim();
        $button.text('Saving...');
        const form = $(this).closest('.dweb_ps__settings').find('form').first();
        if (!form.length) {
            $button.text('Save failed');
            setTimeout(() => {
                $button.text(defaultLabel);
            }, 2000);
            $button.parent().find('#dweb_ps__save-settings-error').text('No settings form found on this page.');
            return;
        }
        const endPoint = form.data('source');
        const data = form.serializeArray().reduce(function (obj, item) {
            obj[item.name] = item.value;
            return obj;
        }, {});

        const handleError = (data) => {
            $button.text('Save failed');
            setTimeout(() => {
                $button.text(defaultLabel);
            }, 2000)

            const errors = [];
            if (data && data.data) {
                $.each(data.data, function (key, val) {
                    if (val && val.error) {
                        errors.push(val.error);
                    }
                });
            }
            $button.parent().find('#dweb_ps__save-settings-error').text(errors.join(' | ') || 'Could not save these settings.');
        }

        const handleSuccess = (data) => {
            $button.text('Saved');
            setTimeout(() => {
                $button.text(defaultLabel);
            }, 2000)
            $button.parent().find('#dweb_ps__save-settings-error').html('');
        }

        window.DWEB_PS_ADMIN.sync(endPoint, data).then((data) => {

            if (data.success) {
                handleSuccess(data);
            } else {
                handleError(data)
            }
        }).catch((error) => {
            handleError(error);
            console.warn(error)
        });
    });

    // Test credentials button
    $('body').on('click', '#dweb_ps-test-auth', function (e) {
        e.preventDefault();
        const $btn = $(e.currentTarget);
        const defaultLabel = $btn.text().trim();
        const $result = $('#dweb_ps__check-auth-result');
        const $form = $btn.closest('form');
        const values = $form.serializeArray().reduce(function (obj, item) {
            obj[item.name] = item.value;
            return obj;
        }, {});
        $btn.text('Testing...');
        $result.removeClass('dweb_ps__error').removeClass('dweb_ps__success').html('');

        window.DWEB_PS_ADMIN
            .sync('dweb_ps-check-auth', values, 'get')
            .then((res) => {
                console.log(res);
                $btn.text(defaultLabel);
                const teamName = (res && res.data  && res.data.team && res.data.team.name)
                    ? res.data.team.name
                    : null;
                const msg = teamName
                    ? `Successfully connected to ${teamName}.`
                    : (res.data && res.data.message ? res.data.message : res.data.message);
                $result.addClass('dweb_ps__success').text(msg);
            })
            .catch((err) => {
                $btn.text(defaultLabel);
                const msg = (err && err.data && err.data.message) ? err.data.message : (err.message || 'Authentication failed.');
                $result.addClass('dweb_ps__error').text(msg);
                console.warn(err);
            });
    });
});
