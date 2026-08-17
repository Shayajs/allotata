{{-- Base publique de l'API pour les apps front (autocomplete, etc.). --}}
<meta name="allotata-api" content="{{ \App\Support\SubdomainHost::apiBaseUrl('v1') }}">
<script>
    window.ALLOTATA_API = @json(\App\Support\SubdomainHost::apiBaseUrl('v1'));
</script>
