<head>
    <title>{{ $sheet->name }}</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/handsontable/2.0.0/handsontable.min.css">
    <!-- Fonts -->
    <link rel="dns-prefetch" href="https://fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css?family=Raleway:300,400,600" rel="stylesheet" type="text/css">

    <!-- Styles -->
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
    <style>
        .avatar {
            color: rgb(255, 255, 255);
            background-color: #fc0093;
            display: inline-block;
            font-family: Arial, sans-serif;
            font-size: 20px;
            border-radius: 50%;
            width: 36px;
            height: 36px;
            text-align: center;
        }
    </style>
</head>

<br>
<h2>{{ $sheet->name }}</h2>
<p>
    <span style="float: right; margin-right: 50px; margin-bottom: 40px; font-size: 16px;">Now viewing: <span id="viewers"></span>
    </span>
</p>
<br> <br>

<div id="sheet"></div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/handsontable/2.0.0/handsontable.min.js"></script>
<script>
    let csrfToken = document.head.querySelector('meta[name="csrf-token"]').content;
    let sheetContent = @json($sheet->content);

    let container = document.getElementById('sheet');
    let table = new Handsontable(container, {
        data: sheetContent,
        rowHeaders: true,
        colHeaders: true,
        minCols: 20,
        minRows: 20,
        afterChange: function (change, source) {
            if (source === 'loadData') return;

            console.log(change, source);

            fetch('/sheets/{{ $sheet->_id }}', {
                method: 'PUT',
                body: JSON.stringify({ change: change[0] }),
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Content-Type': 'application/json'
                },
                credentials: 'same-origin'
            });
        }
    });

</script>
<script src="{{ asset('js/app.js') }}"></script>
<script src="https://js.pusher.com/4.2/pusher.min.js"></script>
<script>
    let pusher = new Pusher("{{ env('PUSHER_APP_KEY') }}", {
        cluster: "{{ env('PUSHER_APP_CLUSTER') }}",
        authEndpoint: '/sheets/{{ $sheet->_id }}/subscription_auth',
        auth: {
            headers: {
                'X-CSRF-Token': csrfToken
            }
        }
    });

    pusher.subscribe("{{ $sheet->channel_name }}")
        .bind('pusher:subscription_succeeded', (data) => {
            Object.entries(data.members)
                .forEach(([id, member]) => addViewer(member));
        })
        .bind('pusher:member_added', (member) => addViewer(member.info))
        .bind('pusher:member_removed', (member) => removeViewer(member))
        .bind('updated', function (message) {
            let [rowIndex, columnIndex, oldValue, newValue] = message.change;
            addCellValue(rowIndex, columnIndex, newValue);
            table.loadData(sheetContent);
        });

    function addCellValue(rowIndex, columnIndex, newValue) {
        // we expand the sheet to reach the farthest cell
        for (let row = 0; row <= rowIndex; row++) {
            if (!sheetContent[row]) sheetContent[row] = [];
            for (let column = 0; column <= columnIndex; column++) {
                if (!sheetContent[row][column])
                    sheetContent[row][column] = null;
            }
        }

        sheetContent[rowIndex][columnIndex] = newValue;
    }

</script>
<script>
    function addViewer(viewer) {
        const userInitials = viewer.name.split(' ')
            .reduce((initials, name) => {
                initials.push(name[0]);
                return initials;
            }, []).join('');
        let $avatar = $('<span>')
            .addClass('avatar')
            .attr('data-toggle', 'tooltip')
            .attr('id', `avatar-${viewer._id}`)
            .attr('title', viewer.name)
            .text(userInitials);
        $('#viewers').append($avatar);
        // enable the tooltip
        $avatar.tooltip();
    }

    function removeViewer(viewer) {
        $(`#avatar-${viewer.id}`).remove();
    }

</script>
