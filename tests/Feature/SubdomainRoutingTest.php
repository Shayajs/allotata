<?php

namespace Tests\Feature;

use App\Models\Entreprise;
use App\Models\EntrepriseSubscription;
use App\Models\User;
use App\Support\SubdomainHost;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class SubdomainRoutingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'subdomains.enabled' => true,
            'subdomains.base_domain' => 'allotata.test',
            'app.url' => 'https://allotata.test',
        ]);
    }

    public function test_liens_apex_m_p_w_inchanges(): void
    {
        $user = User::factory()->create(['est_gerant' => true]);
        $entreprise = Entreprise::factory()->create([
            'user_id' => $user->id,
            'slug' => 'acme',
            'slug_web' => 'acme-web',
            'est_verifiee' => true,
        ]);

        $this->actingAs($user)->get('https://allotata.test/m/acme')->assertOk();
        $this->actingAs($user)->get('https://allotata.test/p/acme')->assertOk();
        $this->actingAs($user)->get('https://allotata.test/w/acme-web')->assertOk();

        auth()->logout();

        $this->get('https://allotata.test/p/acme')->assertNotFound();
        $this->get('https://allotata.test/w/acme-web')->assertNotFound();
        $this->assertSame($entreprise->id, $entreprise->fresh()->id);
    }

    public function test_tenant_manage_et_public_reecrivent_vers_m_et_p(): void
    {
        $user = User::factory()->create(['est_gerant' => true]);
        Entreprise::factory()->create([
            'user_id' => $user->id,
            'slug' => 'acme',
            'est_verifiee' => true,
        ]);

        $this->get('https://acme.allotata.test/manage')
            ->assertRedirect();

        $this->actingAs($user)
            ->get('https://acme.allotata.test/manage')
            ->assertOk()
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow');

        $this->actingAs($user)
            ->get('https://acme.allotata.test/public')
            ->assertOk();
    }

    public function test_tenant_racine_sans_site_web_redirige_vers_manage(): void
    {
        $user = User::factory()->create(['est_gerant' => true]);
        Entreprise::factory()->create([
            'user_id' => $user->id,
            'slug' => 'acme',
        ]);

        $this->get('https://acme.allotata.test/')
            ->assertRedirect('https://acme.allotata.test/manage');
    }

    public function test_les_pages_publiques_d_une_entreprise_restent_indexables(): void
    {
        [$user] = $this->entreprisePubliee();

        // Ouvertes aux visiteurs depuis l'apex hier, depuis le sous-domaine
        // aujourd'hui : dans les deux cas les moteurs y ont leur place.
        foreach (['/', '/public', '/public/agenda'] as $chemin) {
            $this->get('https://acme.allotata.test'.$chemin)
                ->assertOk()
                ->assertHeaderMissing('X-Robots-Tag');
        }

        // L'espace de gestion de la meme entreprise n'est pas public.
        $this->actingAs($user)
            ->get('https://acme.allotata.test/manage')
            ->assertOk()
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow');
    }

    public function test_les_sous_domaines_de_service_restent_hors_des_moteurs(): void
    {
        foreach ([
            'https://dash.allotata.test/',
            'https://admin.allotata.test/',
            'https://sign.allotata.test/',
        ] as $url) {
            $this->get($url)->assertHeader('X-Robots-Tag', 'noindex, nofollow');
        }
    }

    public function test_les_pages_publiques_designent_une_seule_url_canonique(): void
    {
        $this->entreprisePubliee();

        // Depuis le sous-domaine de l'entreprise.
        $this->get('https://acme.allotata.test/public')
            ->assertSee('<link rel="canonical" href="https://acme.allotata.test/public">', false);

        $this->get('https://acme.allotata.test/')
            ->assertSee('<link rel="canonical" href="https://acme.allotata.test/">', false);

        // Et depuis l'ancien lien de l'apex, meme quand il le sert au lieu de rediriger :
        // c'est toujours le sous-domaine qui est designe.
        config(['subdomains.legacy_redirect' => false]);

        $this->get('https://allotata.test/p/acme')
            ->assertOk()
            ->assertSee('<link rel="canonical" href="https://acme.allotata.test/public">', false);
    }

    /**
     * Une entreprise reellement visible des visiteurs : l'abonnement du gerant ouvre
     * le profil public, celui de l'entreprise ouvre la vitrine.
     *
     * @return array{0: User, 1: Entreprise}
     */
    private function entreprisePubliee(): array
    {
        $user = User::factory()->create([
            'est_gerant' => true,
            'abonnement_manuel' => true,
            'abonnement_manuel_actif_jusqu' => now()->addMonth(),
        ]);

        $entreprise = Entreprise::factory()->create([
            'user_id' => $user->id,
            'slug' => 'acme',
            'slug_web' => 'acme-web',
            'est_verifiee' => true,
        ]);

        EntrepriseSubscription::create([
            'entreprise_id' => $entreprise->id,
            'type' => 'site_web',
            'name' => 'site_web',
            'est_manuel' => true,
            'actif_jusqu' => now()->addMonth(),
        ]);

        return [$user, $entreprise];
    }

    public function test_tenant_racine_avec_site_web_sert_la_vitrine(): void
    {
        $user = User::factory()->create(['est_gerant' => true]);
        $entreprise = Entreprise::factory()->create([
            'user_id' => $user->id,
            'slug' => 'acme',
            'slug_web' => 'acme-web',
            'est_verifiee' => true,
        ]);

        EntrepriseSubscription::create([
            'entreprise_id' => $entreprise->id,
            'type' => 'site_web',
            'name' => 'site_web',
            'est_manuel' => true,
            'actif_jusqu' => now()->addMonth(),
        ]);

        $this->actingAs($user)
            ->get('https://acme.allotata.test/')
            ->assertOk();
    }

    public function test_slug_inconnu_en_404(): void
    {
        $this->get('https://inconnu.allotata.test/manage')->assertNotFound();
        $this->get('https://inconnu.allotata.test/')->assertNotFound();
    }

    public function test_mail_et_reserve_non_mappe_en_404(): void
    {
        $this->get('https://mail.allotata.test/')->assertNotFound();
        $this->get('https://cdn.allotata.test/forum')->assertNotFound();
    }

    public function test_admin_sert_admin_redirige_le_reste_et_404_l_inconnu(): void
    {
        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();

        $this->actingAs($admin)
            ->get('https://admin.allotata.test/')
            ->assertOk()
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow');

        $this->get('https://admin.allotata.test/a-propos')
            ->assertRedirect('https://allotata.test/a-propos');

        $this->get('https://admin.allotata.test/pipo')->assertNotFound();
    }

    public function test_invite_admin_part_vers_sign(): void
    {
        $response = $this->get('https://admin.allotata.test/');

        $response->assertRedirect();
        $this->assertStringContainsString('sign.allotata.test/signin', (string) $response->headers->get('Location'));
        $this->assertStringContainsString('return=', (string) $response->headers->get('Location'));
    }

    public function test_dash_et_admin_reecrivent_la_racine(): void
    {
        $user = User::factory()->create();
        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();

        $this->get('https://dash.allotata.test/')->assertRedirect();
        $this->actingAs($user)->get('https://dash.allotata.test/')->assertOk();

        auth()->logout();

        $this->get('https://admin.allotata.test/')->assertRedirect();
        $this->actingAs($admin)->get('https://admin.allotata.test/')->assertOk();
    }

    public function test_dash_m_redirige_vers_le_tenant(): void
    {
        $user = User::factory()->create(['est_gerant' => true]);
        Entreprise::factory()->create([
            'user_id' => $user->id,
            'slug' => 'acme',
        ]);

        $this->get('https://dash.allotata.test/m/acme')
            ->assertRedirect('https://acme.allotata.test/manage');
    }

    public function test_signin_vit_sur_sign_pas_sur_les_autres_hosts(): void
    {
        $this->get('https://sign.allotata.test/')->assertOk();
        $this->get('https://sign.allotata.test/signin')->assertOk();
        $this->get('https://allotata.test/signin')->assertOk();

        $this->get('https://dash.allotata.test/signin')
            ->assertRedirect('https://sign.allotata.test/');
    }

    public function test_api_v3_n_est_pas_un_404(): void
    {
        $response = $this->get('https://api.allotata.test/v3/HealthCheck');

        $this->assertNotSame(404, $response->status());
    }

    public function test_manifests_par_host(): void
    {
        $user = User::factory()->create(['est_gerant' => true]);
        Entreprise::factory()->create([
            'user_id' => $user->id,
            'slug' => 'acme',
            'nom' => 'Acme Coiffure',
        ]);

        $this->get('https://allotata.test/manifest.json')
            ->assertOk()
            ->assertJsonPath('start_url', '/dashboard');

        $this->get('https://dash.allotata.test/manifest.json')
            ->assertOk()
            ->assertJsonPath('start_url', '/')
            ->assertJsonPath('scope', '/');

        $this->get('https://admin.allotata.test/manifest.json')
            ->assertOk()
            ->assertJsonPath('start_url', '/')
            ->assertJsonPath('short_name', 'Admin');

        $this->get('https://acme.allotata.test/manifest.json')
            ->assertOk()
            ->assertJsonPath('start_url', '/manage')
            ->assertJsonPath('scope', '/manage')
            ->assertJsonPath('name', 'Acme Coiffure')
            ->assertJsonPath('icons.0.src', '/manage/icon/192.png')
            ->assertJsonPath('icons.1.src', '/manage/icon/512.png')
            ->assertJsonPath('icons.0.purpose', 'any')
            ->assertJsonPath('icons.2.purpose', 'maskable');
    }

    public function test_icone_pwa_tenant_reste_sur_la_meme_origine(): void
    {
        $user = User::factory()->create(['est_gerant' => true]);
        Entreprise::factory()->create([
            'user_id' => $user->id,
            'slug' => 'acme',
            'nom' => 'Acme Coiffure',
        ]);

        $icon = $this->get('https://acme.allotata.test/manage/icon/192.png')->assertOk();
        $this->assertStringContainsString('image/png', (string) $icon->headers->get('content-type'));

        $this->actingAs($user)
            ->get('https://acme.allotata.test/manage?tab=installer')
            ->assertOk()
            ->assertSee('/manage/icon/192.png', false)
            ->assertSee('window.installPwa()', false)
            ->assertDontSee('dash.allotata.test/entreprise/acme/icon', false);
    }

    public function test_route_generee_reste_classique_sur_apex(): void
    {
        $this->get('https://allotata.test/signin')->assertOk();

        $this->assertSame(
            'https://allotata.test/m/acme',
            route('entreprise.dashboard', ['slug' => 'acme'])
        );
    }

    public function test_les_liens_sortants_pointent_vers_le_host_proprietaire(): void
    {
        $user = User::factory()->create(['est_gerant' => true]);
        Entreprise::factory()->create([
            'user_id' => $user->id,
            'slug' => 'acme',
            'est_verifiee' => true,
        ]);

        $response = $this->actingAs($user)->get('https://acme.allotata.test/manage');
        $response->assertOk();

        $this->assertSame('https://acme.allotata.test/manage', route('entreprise.dashboard', ['slug' => 'acme']));
        $this->assertSame('https://dash.allotata.test/settings', url('/settings'));
        $this->assertSame('https://allotata.test/forum', url('/forum'));
        $this->assertSame('https://acme.allotata.test/logout', url('/logout'));

        $this->actingAs($user)->get('https://dash.allotata.test/')->assertOk();

        $this->assertSame('https://dash.allotata.test/', url('/dashboard').'/');
        $this->assertSame('https://acme.allotata.test/manage', route('entreprise.dashboard', ['slug' => 'acme']));
        $this->assertSame('https://allotata.test/a-propos', url('/a-propos'));
    }

    public function test_back_utilise_l_url_publique(): void
    {
        $user = User::factory()->create(['est_gerant' => true]);
        Entreprise::factory()->create([
            'user_id' => $user->id,
            'slug' => 'acme',
            'est_verifiee' => true,
        ]);

        $this->actingAs($user)->get('https://acme.allotata.test/manage/agenda')->assertOk();

        $this->assertSame('https://acme.allotata.test/manage/agenda', session()->previousUrl());
    }

    public function test_logout_reste_sur_l_origine_courante(): void
    {
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);

        $user = User::factory()->create(['est_gerant' => true]);
        Entreprise::factory()->create([
            'user_id' => $user->id,
            'slug' => 'acme',
            'est_verifiee' => true,
        ]);

        $this->actingAs($user)
            ->post('https://acme.allotata.test/logout')
            ->assertRedirect('https://acme.allotata.test/');

        $this->actingAs(User::factory()->create())
            ->post('https://dash.allotata.test/logout')
            ->assertRedirect('https://dash.allotata.test/');

        $this->actingAs(User::factory()->create())
            ->post('https://allotata.test/logout')
            ->assertRedirect('https://allotata.test');
    }

    public function test_hors_perimetre_la_navigation_part_mais_pas_l_ecriture(): void
    {
        $this->get('https://admin.allotata.test/contact')
            ->assertRedirect('https://allotata.test/contact');

        $this->assertSame(
            'serve',
            SubdomainHost::decide(Request::create('https://admin.allotata.test/contact', 'POST'))['action']
        );
    }

    public function test_slug_reserve_decale_le_suffixe(): void
    {
        $this->assertSame('admin-1', SubdomainHost::nextAvailableSlug('admin'));
        $this->assertSame('dash-1', SubdomainHost::nextAvailableSlug('dash'));
        $this->assertSame('sign-1', SubdomainHost::nextAvailableSlug('sign'));
    }

    public function test_apex_redirige_les_anciens_liens_vers_le_proprietaire(): void
    {
        config(['subdomains.legacy_redirect' => true]);

        $user = User::factory()->create(['est_gerant' => true]);
        Entreprise::factory()->create([
            'user_id' => $user->id,
            'slug' => 'acme',
            'slug_web' => 'acme-web',
            'est_verifiee' => true,
        ]);

        $this->actingAs($user);

        $this->get('https://allotata.test/dashboard')->assertRedirect('https://dash.allotata.test/');
        $this->get('https://allotata.test/settings')->assertRedirect('https://dash.allotata.test/settings');
        $this->get('https://allotata.test/admin')->assertRedirect('https://admin.allotata.test/');
        $this->get('https://allotata.test/admin/users')->assertRedirect('https://admin.allotata.test/users');
        $this->get('https://allotata.test/signin')->assertRedirect('https://sign.allotata.test/');
        $this->get('https://allotata.test/signup')->assertRedirect('https://sign.allotata.test/signup');
        $this->get('https://allotata.test/m/acme')->assertRedirect('https://acme.allotata.test/manage');
        $this->get('https://allotata.test/m/acme/agenda')->assertRedirect('https://acme.allotata.test/manage/agenda');
        $this->get('https://allotata.test/p/acme')->assertRedirect('https://acme.allotata.test/public');
        $this->get('https://allotata.test/w/acme-web')->assertRedirect('https://acme.allotata.test/');

        $this->get('https://allotata.test/m/acme?tab=reservations')
            ->assertRedirect('https://acme.allotata.test/manage?tab=reservations');
    }

    public function test_apex_ne_redirige_pas_les_chemins_partages(): void
    {
        config(['subdomains.legacy_redirect' => true]);

        $this->get('https://allotata.test/')->assertOk();
        $this->get('https://allotata.test/manifest.json')->assertOk();
        $this->get('https://allotata.test/api/v3/HealthCheck')->assertStatus(200);

        $response = $this->get('https://allotata.test/forum');
        $this->assertNotSame(302, $response->status());
    }

    public function test_apex_preserve_la_methode_et_le_statut_configure(): void
    {
        config(['subdomains.legacy_redirect' => true]);

        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);

        // Filet de securite : une methode non acceptee par la route reste redirigee,
        // et la redirection preserve alors la methode.
        $this->post('https://allotata.test/dashboard')
            ->assertStatus(307)
            ->assertRedirect('https://dash.allotata.test/');

        config(['subdomains.redirect_status' => 301]);

        $this->get('https://allotata.test/dashboard')->assertStatus(301);
        $this->post('https://allotata.test/dashboard')->assertStatus(308);
    }

    public function test_le_perimetre_du_host_prime_sur_les_chemins_partages(): void
    {
        // Sans ca, admin/api/* et admin/media seraient avales par /api et /media.
        $decision = SubdomainHost::decide(Request::create('https://admin.allotata.test/api/media/list', 'GET'));
        $this->assertSame('rewrite', $decision['action']);
        $this->assertSame('/admin/api/media/list', $decision['path']);

        $decision = SubdomainHost::decide(Request::create('https://admin.allotata.test/media', 'GET'));
        $this->assertSame('rewrite', $decision['action']);
        $this->assertSame('/admin/media', $decision['path']);

        // Un chemin partage sans equivalent dans le perimetre reste servi.
        $this->assertSame(
            'serve',
            SubdomainHost::decide(Request::create('https://admin.allotata.test/api/v3/HealthCheck', 'GET'))['action']
        );
        $this->assertSame(
            'serve',
            SubdomainHost::decide(Request::create('https://admin.allotata.test/build/assets/app.js', 'GET'))['action']
        );
    }

    public function test_les_assets_restent_sur_l_origine_de_la_page(): void
    {
        // Vite emet des <script type="module"> : un asset servi depuis un autre
        // sous-domaine est refuse par le navigateur faute d'en-tetes CORS, ce qui
        // tuerait tout le JavaScript compile. ASSET_URL doit rester vide.
        $this->get('https://dash.allotata.test/apprendre');
        $this->assertSame('https://dash.allotata.test/build/app.js', asset('build/app.js'));

        $this->get('https://acme.allotata.test/');
        $this->assertSame('https://acme.allotata.test/build/app.js', asset('build/app.js'));
    }

    public function test_le_consentement_cookies_est_partage_entre_les_sous_domaines(): void
    {
        config(['session.domain' => '.allotata.test']);

        // Le choix doit etre stocke sur le domaine parent : en localStorage, la
        // banniere reapparaitrait sur chaque sous-domaine.
        foreach (['https://allotata.test/', 'https://dash.allotata.test/apprendre'] as $url) {
            $this->get($url)->assertSee('const COOKIE_DOMAIN = ".allotata.test"', false);
        }
    }

    public function test_un_chemin_statique_n_est_jamais_produit_par_traduction(): void
    {
        // /admin/media ne doit pas devenir /media, sinon nginx sert le dossier
        // public/media au lieu de la mediatheque admin.
        $this->assertSame('https://admin.allotata.test/admin/media', SubdomainHost::ownerUrl('/admin/media'));
        $this->assertSame('https://admin.allotata.test/users', SubdomainHost::ownerUrl('/admin/users'));

        $this->get('https://admin.allotata.test/');

        $this->assertSame('https://admin.allotata.test/admin/media', url('/admin/media'));
        $this->assertSame('https://admin.allotata.test/media/logo.png', url('/media/logo.png'));

        $this->assertSame(
            'serve',
            SubdomainHost::decide(Request::create('https://admin.allotata.test/admin/media', 'GET'))['action']
        );
    }

    public function test_les_ecritures_ne_changent_jamais_de_host(): void
    {
        config(['subdomains.legacy_redirect' => true]);

        $user = User::factory()->create(['est_gerant' => true]);
        Entreprise::factory()->create([
            'user_id' => $user->id,
            'slug' => 'acme',
            'est_verifiee' => true,
        ]);

        // Un fetch cross-perimetre serait bloque par CORS : on sert sur place.
        foreach ([
            'https://acme.allotata.test/settings/confidentialite',
            'https://dash.allotata.test/security/google2fa/enable',
            'https://allotata.test/settings/confidentialite',
            'https://admin.allotata.test/settings/confidentialite',
        ] as $url) {
            $this->assertSame(
                'serve',
                SubdomainHost::decide(Request::create($url, 'POST'))['action'],
                $url
            );
        }

        // Et l'URL generee reste sur le host courant.
        $this->actingAs($user)->get('https://acme.allotata.test/manage')->assertOk();
        $this->assertSame('https://acme.allotata.test/settings/confidentialite', url('/settings/confidentialite'));

        $this->get('https://allotata.test/');
        $this->assertSame('https://allotata.test/settings/confidentialite', url('/settings/confidentialite'));
    }

    public function test_la_methode_usurpee_est_prise_en_compte(): void
    {
        Request::enableHttpMethodParameterOverride();

        $user = User::factory()->create(['est_gerant' => true]);
        Entreprise::factory()->create([
            'user_id' => $user->id,
            'slug' => 'acme',
            'est_verifiee' => true,
        ]);

        $request = Request::create(
            'https://acme.allotata.test/settings/entreprise/acme/logo',
            'POST',
            ['_method' => 'DELETE']
        );

        $this->assertSame('DELETE', $request->getMethod());
        $this->assertSame('serve', SubdomainHost::decide($request)['action']);
    }

    public function test_la_vitrine_laisse_l_application_gerer_l_abonnement(): void
    {
        $user = User::factory()->create(['est_gerant' => true]);
        Entreprise::factory()->create([
            'user_id' => $user->id,
            'slug' => 'acme',
            'est_verifiee' => true,
        ]);

        // Seule la navigation vers la racine bascule sur /manage.
        $this->get('https://acme.allotata.test/')
            ->assertRedirect('https://acme.allotata.test/manage');

        // Le reste de l'espace vitrine est reecrit, comme sur l'apex.
        $decision = SubdomainHost::decide(Request::create('https://acme.allotata.test/reservation-form', 'GET'));
        $this->assertSame('rewrite', $decision['action']);
        $this->assertSame('/w/acme/reservation-form', $decision['path']);

        // Le favicon du tenant gagne sur le favicon partage.
        $decision = SubdomainHost::decide(Request::create('https://acme.allotata.test/favicon.png', 'GET'));
        $this->assertSame('rewrite', $decision['action']);
        $this->assertSame('/w/acme/favicon.png', $decision['path']);
    }

    public function test_les_boutons_de_l_apex_pointent_vers_le_proprietaire(): void
    {
        config(['subdomains.legacy_redirect' => true]);

        $user = User::factory()->create(['est_gerant' => true]);
        Entreprise::factory()->create([
            'user_id' => $user->id,
            'slug' => 'acme',
            'est_verifiee' => true,
        ]);

        $this->get('https://allotata.test/')->assertOk();

        $this->assertSame('https://dash.allotata.test/settings', url('/settings'));
        $this->assertSame('https://sign.allotata.test/signup', url('/signup'));
        $this->assertSame('https://acme.allotata.test/manage', route('entreprise.dashboard', ['slug' => 'acme']));
        $this->assertSame('https://allotata.test/forum', url('/forum'));
        $this->assertSame('https://allotata.test/api/v3/HealthCheck', url('/api/v3/HealthCheck'));
    }

    public function test_legacy_off_laisse_l_apex_servir_ses_anciens_liens(): void
    {
        config(['subdomains.legacy_redirect' => false]);

        $user = User::factory()->create(['est_gerant' => true]);
        Entreprise::factory()->create([
            'user_id' => $user->id,
            'slug' => 'acme',
            'est_verifiee' => true,
        ]);

        $this->actingAs($user)->get('https://allotata.test/m/acme')->assertOk();
        $this->actingAs($user)->get('https://allotata.test/dashboard')->assertOk();
        $this->assertSame('https://allotata.test/m/acme', route('entreprise.dashboard', ['slug' => 'acme']));
    }

    public function test_flag_off_ignore_le_sous_domaine(): void
    {
        config(['subdomains.enabled' => false]);

        $user = User::factory()->create(['est_gerant' => true]);
        Entreprise::factory()->create([
            'user_id' => $user->id,
            'slug' => 'acme',
        ]);

        $this->actingAs($user)
            ->get('https://acme.allotata.test/manage')
            ->assertNotFound();

        $this->actingAs($user)
            ->get('https://allotata.test/m/acme')
            ->assertOk();
    }
}
