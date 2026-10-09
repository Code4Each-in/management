<?php

namespace Database\Seeders;

use App\Models\UpworkClassification;
use App\Models\UpworkRuleset;
use App\Models\UpworkSearchProfile;
use App\Models\UpworkSetting;
use App\Models\UpworkTechnology;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Default configuration for the Upwork intelligence engine.
 *
 * Idempotent and non-destructive: it only creates what is missing, never
 * overwrites anything an admin has edited in the PMS, and only creates the
 * v1 ruleset when no ruleset has ever been published.
 *
 *   php artisan db:seed --class=UpworkIntelligenceSeeder
 */
class UpworkIntelligenceSeeder extends Seeder
{
    const EVIDENCE_PROFILE = 'Listed skill on the Code4Each Upwork agency profile.';
    const EVIDENCE_360 = 'Portfolio: 360Alumni SaaS (PHP/Symfony microservices, ReactJS UI architecture, Kendo UI, Storybook).';
    const EVIDENCE_GHL = 'Portfolio: GoHighLevel (~2 years: funnels, automations, Mailgun, payment plans; WordPress tooling for 5,000+ sites, custom plugins/themes).';
    const EVIDENCE_LBH = 'Portfolio: Legacy Beach Homes (booking engine, Airbnb/VRBO sync, payment gateways, calendar, PriceLabs, user dashboards).';
    const EVIDENCE_MCW = 'Portfolio: Mid-Century Warehouse (legacy WordPress e-commerce modernization, new product lines, inventory).';
    const EVIDENCE_PMS = 'This Code4Each PMS is a production Laravel + MySQL application.';

    public function run()
    {
        DB::transaction(function () {
            $this->seedTechnologies();
            $this->seedSearchProfiles();
            $this->seedClassifications();
            $this->seedRuleset();
            $this->seedSettings();
        });
    }

    /**
     * [name, slug, category, tier, evidence|null, aliases[], notes|null]
     */
    private function technologies(): array
    {
        $p = UpworkTechnology::TIER_PROVEN;
        $a = UpworkTechnology::TIER_ADJACENT;
        $e = UpworkTechnology::TIER_EMERGING;
        $x = UpworkTechnology::TIER_EXCLUDED;

        return [
            // Backend
            ['PHP', 'php', 'backend', $p, self::EVIDENCE_PROFILE . ' ' . self::EVIDENCE_360, ['php7', 'php8', 'php 8']],
            ['Laravel', 'laravel', 'backend', $p, self::EVIDENCE_PROFILE . ' ' . self::EVIDENCE_PMS, ['laravel framework', 'lumen', 'eloquent', 'livewire']],
            ['Symfony', 'symfony', 'backend', $p, self::EVIDENCE_PROFILE . ' ' . self::EVIDENCE_360, ['symfony framework', 'symfony stimulus']],
            ['CodeIgniter', 'codeigniter', 'backend', $p, self::EVIDENCE_PROFILE, ['code igniter', 'ci4']],
            ['CakePHP', 'cakephp', 'backend', $p, self::EVIDENCE_PROFILE, ['cake php']],
            ['Node.js', 'nodejs', 'backend', $p, self::EVIDENCE_PROFILE, ['node js', 'express.js', 'expressjs']],
            ['MySQL', 'mysql', 'database', $p, self::EVIDENCE_PROFILE . ' ' . self::EVIDENCE_PMS, ['mariadb']],
            ['REST API', 'rest-api', 'integration', $p, self::EVIDENCE_PROFILE . ' (API Development, Web Services Development)', ['restful api', 'rest apis', 'api development', 'web services', 'json api']],
            ['PostgreSQL', 'postgresql', 'database', $a, null, ['postgres', 'pgsql']],
            ['Redis', 'redis', 'database', $a, null, []],
            ['Python', 'python', 'backend', $a, null, ['django', 'flask', 'fastapi'], 'No Code4Each Python portfolio item recorded.'],
            ['TypeScript', 'typescript', 'frontend', $a, null, []],
            ['GraphQL', 'graphql', 'integration', $a, null, []],

            // Frontend
            ['React', 'react', 'frontend', $p, self::EVIDENCE_360, ['reactjs', 'react.js', 'react js']],
            ['Vue.js', 'vuejs', 'frontend', $p, self::EVIDENCE_PROFILE, ['vue', 'vue js', 'vue.js', 'nuxt']],
            ['Angular', 'angular', 'frontend', $p, self::EVIDENCE_PROFILE, ['angularjs', 'angular.js']],
            ['Kendo UI', 'kendo-ui', 'frontend', $p, self::EVIDENCE_PROFILE . ' ' . self::EVIDENCE_360, ['kendoui']],
            ['Next.js', 'nextjs', 'frontend', $a, null, ['next js', 'next.js']],
            ['Flutter', 'flutter', 'mobile', $x, null, ['dart'], 'No Code4Each Flutter apps recorded. Change the tier if this capability exists.'],
            ['Native iOS', 'native-ios', 'mobile', $x, null, ['swift', 'swiftui', 'objective-c', 'xcode']],
            ['Native Android', 'native-android', 'mobile', $x, null, ['kotlin', 'android studio', 'jetpack compose']],

            // CMS / e-commerce
            ['WordPress', 'wordpress', 'cms', $p, self::EVIDENCE_PROFILE . ' ' . self::EVIDENCE_GHL . ' ' . self::EVIDENCE_MCW, ['wp', 'wordpress plugin', 'wordpress theme', 'elementor', 'gutenberg']],
            ['WooCommerce', 'woocommerce', 'ecommerce', $a, null, ['woo commerce'], 'Mid-Century Warehouse is WordPress e-commerce, but WooCommerce is not named in the portfolio.'],
            ['Shopify', 'shopify', 'ecommerce', $a, null, ['shopify app', 'liquid']],
            ['E-commerce', 'ecommerce', 'domain', $p, self::EVIDENCE_PROFILE . ' (Ecommerce Website Development) ' . self::EVIDENCE_MCW, ['e-commerce', 'online store', 'webshop']],

            // Domains / project types
            ['SaaS', 'saas', 'domain', $p, self::EVIDENCE_360 . ' ' . self::EVIDENCE_GHL, ['software as a service', 'multi-tenant', 'multitenant', 'subscription platform']],
            ['CRM', 'crm', 'domain', $p, self::EVIDENCE_GHL . ' GoHighLevel is a CRM platform.', ['customer relationship management', 'hubspot', 'salesforce', 'pipedrive', 'zoho crm']],
            ['GoHighLevel', 'gohighlevel', 'automation', $p, self::EVIDENCE_GHL, ['go high level', 'highlevel', 'ghl', 'leadconnector']],
            ['ERP', 'erp', 'domain', $a, null, ['enterprise resource planning', 'odoo', 'sap', 'netsuite'], 'Agency summary mentions enterprise portals; no ERP portfolio item recorded.'],
            ['Dashboard', 'dashboard', 'domain', $p, self::EVIDENCE_LBH . ' ' . self::EVIDENCE_PMS, ['admin panel', 'admin dashboard', 'reporting dashboard', 'back office']],
            ['Legacy Modernization', 'legacy-modernization', 'domain', $p, self::EVIDENCE_MCW . ' ' . self::EVIDENCE_360 . ' (legacy to Monarch redesign).', ['legacy php', 'legacy code', 'legacy system', 'legacy application', 'modernize', 'modernization', 'modernisation', 'upgrade laravel', 'php upgrade']],
            ['Booking Engine', 'booking-engine', 'domain', $p, self::EVIDENCE_LBH, ['booking platform', 'booking system', 'reservation', 'reservations', 'appointment booking', 'scheduling system', 'availability calendar', 'channel manager']],
            ['Payment Gateway', 'payment-gateway', 'integration', $p, self::EVIDENCE_LBH . ' ' . self::EVIDENCE_GHL, ['payment integration', 'payment gateways', 'paypal', 'braintree', 'razorpay']],
            ['Stripe', 'stripe', 'integration', $a, null, ['stripe connect', 'stripe api', 'stripe webhook']],
            ['API Integration', 'api-integration', 'integration', $p, self::EVIDENCE_LBH . ' (Airbnb/VRBO, PriceLabs) ' . self::EVIDENCE_GHL . ' (Mailgun)', ['third-party api', 'third party api', 'api integrations', 'integrate api', 'webhook integration', 'data sync', 'system integration']],
            ['Webhooks', 'webhooks', 'integration', $p, self::EVIDENCE_GHL, ['webhook']],
            ['Mailgun', 'mailgun', 'integration', $p, self::EVIDENCE_GHL, []],

            // AI / automation
            ['Artificial Intelligence', 'ai', 'ai', $e, null, ['ai', 'artificial intelligence', 'machine learning model', 'genai', 'generative ai'], 'Agency summary claims AI workflow automation; no AI portfolio item recorded yet.'],
            ['OpenAI', 'openai', 'ai', $e, null, ['chatgpt', 'chat gpt', 'gpt', 'gpt-4', 'gpt-4o', 'gpt api', 'openai api']],
            ['Claude', 'claude', 'ai', $e, null, ['anthropic', 'claude api', 'anthropic api']],
            ['Gemini', 'gemini', 'ai', $e, null, ['google gemini', 'gemini api']],
            ['LLM', 'llm', 'ai', $e, null, ['llms', 'large language model', 'large language models', 'prompt engineering', 'function calling', 'tool calling']],
            ['AI Agents', 'ai-agents', 'ai', $e, null, ['ai agent', 'agentic', 'autonomous agent', 'multi-agent', 'ai assistant', 'chatbot', 'ai chatbot', 'voice agent']],
            ['RAG', 'rag', 'ai', $e, null, ['retrieval augmented generation', 'retrieval-augmented generation', 'embeddings', 'vector database', 'pinecone', 'pgvector', 'qdrant', 'weaviate', 'semantic search']],
            ['LangChain', 'langchain', 'ai', $e, null, ['langgraph', 'llamaindex']],
            ['OCR / Document AI', 'document-ai', 'ai', $e, null, ['ocr', 'document processing', 'data extraction', 'pdf extraction', 'document parsing']],
            ['Claude Code', 'claude-code', 'ai', $e, null, []],
            ['Workflow Automation', 'workflow-automation', 'automation', $e, null, ['business automation', 'process automation', 'automation workflow', 'automate workflows'], 'Agency summary: "automate workflows using AI". GoHighLevel automations are PROVEN separately.'],
            ['n8n', 'n8n', 'automation', $e, null, []],
            ['Make.com', 'make', 'automation', $e, null, ['make.com', 'integromat'], 'Matched only as "make.com"/"integromat": the bare word "make" is too common.'],
            ['Zapier', 'zapier', 'automation', $e, null, []],
            ['Supabase', 'supabase', 'backend', $e, null, []],
            ['Microsoft Entra ID', 'entra-id', 'integration', $e, null, ['azure ad', 'azure active directory', 'entra id']],

            // DevOps
            ['AWS', 'aws', 'devops', $a, null, ['amazon web services', 'ec2', 's3', 'rds', 'lambda']],
            ['Docker', 'docker', 'devops', $a, null, ['docker compose']],
            ['Kubernetes-heavy DevOps', 'kubernetes', 'devops', $x, null, ['kubernetes', 'k8s', 'helm', 'eks', 'gke']],

            // Clearly outside engineering capability
            ['Deep ML Research', 'ml-research', 'ai', $x, null, ['model training', 'train a model', 'computer vision research', 'pytorch', 'tensorflow', 'fine-tuning research']],
            ['Video Production', 'video-production', 'non-engineering', $x, null, ['video editing', 'video editor', 'youtube editor', 'premiere pro', 'after effects']],
            ['Paid Ads / SEO Marketing', 'marketing', 'non-engineering', $x, null, ['google ads', 'facebook ads', 'seo specialist', 'media buyer', 'social media marketing']],
            ['Sales / Lead Generation', 'sales', 'non-engineering', $x, null, ['cold calling', 'appointment setting', 'sales closer', 'lead generation', 'telemarketing']],
        ];
    }

    /**
     * [from slug, to slug, weight, type]
     */
    private function relations(): array
    {
        return [
            ['laravel', 'php', 0.9, 'uses'], ['symfony', 'php', 0.9, 'uses'], ['codeigniter', 'php', 0.9, 'uses'],
            ['cakephp', 'php', 0.9, 'uses'], ['laravel', 'symfony', 0.6, 'alternative'], ['laravel', 'mysql', 0.6, 'uses'],
            ['laravel', 'rest-api', 0.6, 'related'], ['laravel', 'saas', 0.5, 'related'], ['laravel', 'dashboard', 0.5, 'related'],
            ['php', 'legacy-modernization', 0.4, 'related'], ['php', 'mysql', 0.5, 'related'],
            ['react', 'nextjs', 0.6, 'related'], ['react', 'typescript', 0.5, 'related'], ['vuejs', 'react', 0.5, 'alternative'],
            ['angular', 'typescript', 0.6, 'uses'], ['nodejs', 'typescript', 0.5, 'related'],
            ['mysql', 'postgresql', 0.6, 'alternative'], ['laravel', 'redis', 0.4, 'related'],
            ['saas', 'dashboard', 0.5, 'related'], ['saas', 'crm', 0.4, 'related'], ['saas', 'stripe', 0.4, 'related'],
            ['crm', 'gohighlevel', 0.7, 'related'], ['crm', 'erp', 0.4, 'related'], ['crm', 'api-integration', 0.5, 'related'],
            ['erp', 'api-integration', 0.5, 'related'], ['erp', 'dashboard', 0.4, 'related'],
            ['wordpress', 'woocommerce', 0.7, 'related'], ['woocommerce', 'ecommerce', 0.8, 'related'], ['shopify', 'ecommerce', 0.8, 'related'],
            ['booking-engine', 'payment-gateway', 0.6, 'related'], ['booking-engine', 'api-integration', 0.5, 'related'],
            ['payment-gateway', 'stripe', 0.7, 'related'], ['api-integration', 'rest-api', 0.8, 'related'], ['api-integration', 'webhooks', 0.7, 'related'],
            ['openai', 'llm', 0.8, 'related'], ['claude', 'llm', 0.8, 'related'], ['gemini', 'llm', 0.8, 'related'],
            ['llm', 'ai', 0.8, 'related'], ['openai', 'ai-agents', 0.6, 'related'], ['openai', 'rag', 0.6, 'related'],
            ['ai-agents', 'workflow-automation', 0.6, 'related'], ['rag', 'llm', 0.7, 'related'], ['langchain', 'rag', 0.7, 'related'],
            ['n8n', 'workflow-automation', 0.8, 'related'], ['make', 'workflow-automation', 0.8, 'related'], ['zapier', 'workflow-automation', 0.8, 'related'],
            ['n8n', 'webhooks', 0.6, 'uses'], ['n8n', 'openai', 0.5, 'related'], ['n8n', 'claude', 0.5, 'related'], ['n8n', 'api-integration', 0.6, 'related'],
            ['gohighlevel', 'workflow-automation', 0.7, 'related'], ['document-ai', 'llm', 0.5, 'related'],
            ['supabase', 'postgresql', 0.7, 'uses'], ['aws', 'docker', 0.4, 'related'],
        ];
    }

    private function seedTechnologies(): void
    {
        foreach ($this->technologies() as $row) {
            [$name, $slug, $category, $tier, $evidence, $aliases] = $row;
            $notes = $row[6] ?? null;

            $technology = UpworkTechnology::firstOrCreate(['slug' => $slug], [
                'name' => $name, 'category' => $category, 'tier' => $tier, 'evidence' => $evidence, 'notes' => $notes,
            ]);

            foreach (array_merge([$name], $aliases) as $alias) {
                if (!\App\Models\UpworkTechnologyAlias::where('alias_normalized', \App\Models\UpworkTechnologyAlias::normalize($alias))->exists()) {
                    $technology->addAlias($alias);
                }
            }
        }

        $bySlug = UpworkTechnology::pluck('id', 'slug');
        foreach ($this->relations() as [$from, $to, $weight, $type]) {
            if (isset($bySlug[$from], $bySlug[$to])) {
                \App\Models\UpworkTechnologyRelation::firstOrCreate(
                    ['technology_id' => $bySlug[$from], 'related_technology_id' => $bySlug[$to]],
                    ['weight' => $weight, 'relation_type' => $type]
                );
            }
        }
    }

    private function seedSearchProfiles(): void
    {
        $common = ['junior', 'internship', 'intern', 'unpaid', 'training', 'cheap developer', 'equity only', 'commission only'];
        $filters = ['verified_payment_only' => true, 'sort' => 'recency', 'pages' => 1];

        $profiles = [
            ['Laravel / PHP', 'laravel-php', 'backend', 10,
                ['Laravel', 'PHP', 'Symfony', 'CodeIgniter', 'legacy PHP'], ['Laravel'],
                ['SaaS', 'CRM', 'ERP', 'dashboard', 'API integration', 'MySQL', 'PostgreSQL', 'Redis'],
                ['laravel', 'php', 'symfony', 'codeigniter', 'cakephp', 'mysql', 'rest-api', 'legacy-modernization']],
            ['AI Automation', 'ai-automation', 'ai', 20,
                ['OpenAI', 'ChatGPT', 'Claude', 'Gemini', 'AI agent', 'n8n', 'Make.com', 'Zapier', 'RAG', 'LLM', 'AI automation'], ['AI automation'],
                ['workflow automation', 'document processing', 'OCR', 'webhooks', 'CRM automation'],
                ['ai', 'openai', 'claude', 'gemini', 'llm', 'ai-agents', 'rag', 'n8n', 'make', 'zapier', 'workflow-automation', 'document-ai']],
            ['SaaS', 'saas', 'domain', 30,
                ['SaaS platform', 'SaaS development', 'multi-tenant', 'subscription platform'], ['SaaS'],
                ['dashboard', 'Stripe', 'Laravel', 'React'],
                ['saas', 'dashboard', 'stripe', 'laravel', 'react']],
            ['React / Full Stack', 'react-full-stack', 'frontend', 40,
                ['React', 'full stack developer', 'React Laravel', 'Vue.js', 'Next.js'], ['React'],
                ['TypeScript', 'Node.js', 'dashboard'],
                ['react', 'vuejs', 'nextjs', 'typescript', 'nodejs', 'angular']],
            ['WordPress', 'wordpress', 'cms', 50,
                ['WordPress plugin', 'WordPress developer', 'custom WordPress theme'], ['WordPress'],
                ['WooCommerce', 'PHP', 'legacy modernization'],
                ['wordpress', 'woocommerce', 'php']],
            ['WooCommerce', 'woocommerce', 'ecommerce', 60,
                ['WooCommerce', 'WooCommerce plugin', 'e-commerce WordPress'], ['WooCommerce'],
                ['payment gateway', 'inventory', 'Stripe'],
                ['woocommerce', 'ecommerce', 'payment-gateway']],
            ['ERP', 'erp', 'domain', 70,
                ['ERP', 'Odoo', 'ERP integration', 'inventory management system'], ['ERP'],
                ['API integration', 'dashboard', 'Laravel'],
                ['erp', 'api-integration', 'dashboard']],
            ['CRM', 'crm', 'domain', 80,
                ['CRM', 'GoHighLevel', 'HubSpot integration', 'Salesforce integration', 'custom CRM'], ['CRM'],
                ['automation', 'API integration', 'pipelines'],
                ['crm', 'gohighlevel', 'api-integration', 'workflow-automation']],
            ['Booking Platforms', 'booking-platforms', 'domain', 90,
                ['booking engine', 'reservation', 'appointment', 'calendar', 'availability', 'scheduling', 'Stripe', 'payment gateway'], ['booking'],
                ['Airbnb', 'VRBO', 'channel manager', 'marketplace'],
                ['booking-engine', 'payment-gateway', 'stripe', 'api-integration']],
            ['API Integration', 'api-integration', 'integration', 100,
                ['API integration', 'third-party API', 'webhook', 'REST API', 'data sync'], ['API integration'],
                ['Stripe', 'CRM', 'ERP', 'Zapier'],
                ['api-integration', 'rest-api', 'webhooks', 'graphql']],
            ['AI Agents', 'ai-agents', 'ai', 110,
                ['AI agent', 'AI chatbot', 'agentic', 'AI assistant', 'voice agent', 'MCP server'], ['AI agent'],
                ['RAG', 'LangChain', 'OpenAI', 'Claude'],
                ['ai-agents', 'rag', 'langchain', 'openai', 'claude', 'llm']],
        ];

        $bySlug = UpworkTechnology::pluck('id', 'slug');

        // Conservative defaults: HOT ~3 searches every 20 min, NORMAL every 2 h,
        // DEEP a few times a day, EXPLORATORY daily. Adjustable in the PMS.
        $tiers = [
            'laravel-php' => 'hot', 'ai-automation' => 'hot',
            'saas' => 'normal', 'react-full-stack' => 'normal', 'api-integration' => 'normal', 'booking-platforms' => 'normal', 'ai-agents' => 'normal',
            'wordpress' => 'deep', 'woocommerce' => 'deep', 'crm' => 'deep',
            'erp' => 'exploratory',
        ];
        $tierFrequency = ['hot' => 20, 'normal' => 120, 'deep' => 480, 'exploratory' => 1440];
        $tierBudget = ['hot' => 120, 'normal' => 36, 'deep' => 18, 'exploratory' => 6];

        foreach ($profiles as [$name, $slug, $category, $priority, $keywords, $phrases, $optional, $techSlugs]) {
            if (UpworkSearchProfile::where('slug', $slug)->exists()) {
                continue;
            }

            $profile = UpworkSearchProfile::create([
                'name' => $name,
                'slug' => $slug,
                'description' => "Default {$name} discovery profile.",
                'category' => $category,
                'priority' => $priority,
                'keywords' => $keywords,
                'exact_phrases' => $phrases,
                'optional_keywords' => $optional,
                'excluded_keywords' => $common,
                'upwork_filters' => $filters,
                'tier' => $tiers[$slug] ?? 'normal',
                'frequency_minutes' => $tierFrequency[$tiers[$slug] ?? 'normal'],
                'daily_call_budget' => $tierBudget[$tiers[$slug] ?? 'normal'],
                'max_results' => 10,
            ]);

            $profile->technologies()->sync($bySlug->only($techSlugs)->values()->all());
        }
    }

    private function seedRuleset(): void
    {
        if (UpworkRuleset::whereNotNull('version')->exists() || UpworkRuleset::draft()) {
            return;
        }

        $ruleset = UpworkRuleset::create([
            'weights' => UpworkRuleset::defaultWeights(),
            'thresholds' => ['apply' => 80, 'review' => 70],
            'settings' => [
                'agency_mode' => true,
                'min_hourly_hard' => 12,     // hourly max below this: hard reject
                'min_fixed_hard' => 150,     // fixed budget below this: hard reject
                'min_hourly' => 25,          // hourly max below this: negative rule
                'min_fixed' => 500,          // fixed budget below this: negative rule
                'quick_win_max_fixed' => 3000,
                'strategic_min_weeks' => 12,
                'max_job_age_hours' => 168,  // older postings are hard-rejected (stale)
                'strategic_min' => 65,       // Strategic Value needed for the STRATEGIC archetype
            ],
            'notes' => 'Default Code4Each ruleset (seeded).',
        ]);

        foreach ($this->rules() as $i => $rule) {
            $ruleset->rules()->create(array_merge(['sort' => ($i + 1) * 10], $rule));
        }

        $ruleset->publish(null);
    }

    private function rules(): array
    {
        $hard = fn($key, $name, $reason, $conditions) => ['key' => $key, 'name' => $name, 'type' => 'hard_filter', 'reason' => $reason, 'conditions' => $conditions];
        $score = fn($key, $name, $component, $points, $reason, $conditions) => ['key' => $key, 'name' => $name, 'type' => 'score', 'component' => $component, 'points' => $points, 'reason' => $reason, 'conditions' => $conditions];
        $class = fn($key, $name, $label, $conditions) => ['key' => $key, 'name' => $name, 'type' => 'classification', 'classification' => $label, 'conditions' => $conditions];
        $tech = fn(array $slugs) => ['op' => 'has_any_tech', 'value' => $slugs];

        return [
            // Hard filters (run before scoring)
            $hard('already_hired', 'Already hired', 'The client has already hired for this posting.', ['field' => 'competition.total_hired', 'op' => 'gt', 'value' => 0]),
            $hard('offer_out', 'Offer already out', 'The client has already made an offer on this posting.', ['field' => 'competition.total_offered', 'op' => 'gt', 'value' => 0]),
            $hard('individual_only', 'Individual-only job (agency mode)', 'Client wants individual freelancers only; Code4Each applies as an agency.', ['all' => [
                ['field' => 'settings.agency_mode', 'op' => 'is_true'],
                ['any' => [
                    ['field' => 'job.contractor_type', 'op' => 'eq', 'value' => 'Individuals only'],
                    ['field' => 'signals.is_individual_only', 'op' => 'is_true'],
                ]],
            ]]),
            $hard('impossible_hourly', 'Extremely low hourly rate', 'Hourly maximum is below the hard floor.', ['field' => 'job.hourly_max', 'op' => 'lt', 'value' => ['setting' => 'min_hourly_hard']]),
            $hard('impossible_fixed', 'Extremely low fixed budget', 'Fixed budget is below the hard floor.', ['all' => [
                ['field' => 'job.job_type', 'op' => 'eq', 'value' => 'fixed'],
                ['field' => 'job.fixed_budget', 'op' => 'lt', 'value' => ['setting' => 'min_fixed_hard']],
            ]]),
            $hard('unsupported_capability', 'Clearly unsupported capability', 'The job centres on EXCLUDED capabilities with no proven or adjacent technology.', ['all' => [
                ['op' => 'has_tier', 'value' => 'excluded'],
                ['not' => ['op' => 'has_tier', 'value' => 'proven']],
                ['not' => ['op' => 'has_tier', 'value' => 'adjacent']],
            ]]),
            $hard('missing_description', 'Invalid / incomplete job data', 'The job has no description to analyse.', ['field' => 'job.description', 'op' => 'is_null']),
            $hard('stale_job', 'Job too old', 'The job was posted longer ago than the maximum job age.', ['field' => 'job.age_hours', 'op' => 'gt', 'value' => ['setting' => 'max_job_age_hours']]),

            // Positive rules
            $score('tech_laravel', 'Laravel', 'technical_fit', 10, 'Laravel is a proven Code4Each stack.', $tech(['laravel'])),
            $score('tech_react', 'React', 'technical_fit', 8, 'React is proven (360Alumni).', $tech(['react'])),
            $score('tech_wordpress', 'WordPress', 'technical_fit', 5, 'WordPress is proven (GoHighLevel, Mid-Century Warehouse).', $tech(['wordpress'])),
            $score('tech_api', 'API integration', 'technical_fit', 6, 'API integration is proven (booking sync, GoHighLevel).', $tech(['api-integration', 'rest-api', 'webhooks'])),
            $score('tech_aws', 'AWS', 'technical_fit', 5, 'AWS is adjacent.', $tech(['aws'])),
            $score('tech_saas', 'SaaS', 'long_term', 8, 'SaaS platforms tend to need ongoing work.', $tech(['saas'])),
            $score('ai_general', 'AI', 'ai_automation', 10, 'AI / LLM work is strategic for Code4Each.', $tech(['ai', 'llm', 'ai-agents', 'rag', 'document-ai', 'openai', 'claude', 'gemini', 'langchain'])),
            $score('ai_openai', 'OpenAI', 'ai_automation', 8, 'OpenAI / ChatGPT integration.', $tech(['openai'])),
            $score('ai_claude', 'Claude', 'ai_automation', 8, 'Claude / Anthropic integration.', $tech(['claude'])),
            $score('ai_n8n', 'n8n', 'ai_automation', 7, 'n8n workflow automation.', $tech(['n8n'])),

            $score('won_before', 'Code4Each won work with this client before', 'client_quality', 5, 'PMS records show a previous win with this client.', ['field' => 'client.previous_wins', 'op' => 'gte', 'value' => 1]),
            $score('upwork_previous_client', 'Upwork: previous client', 'client_quality', 5, 'Upwork reports Code4Each has worked with this client before.', ['field' => 'signals.previous_client', 'op' => 'is_true']),
            $score('long_term_wording', 'Ongoing / expansion wording', 'long_term', 3, 'The posting describes ongoing work or expansion.', ['field' => 'job.text', 'op' => 'contains_any', 'value' => ['ongoing', 'long-term', 'long term', 'retainer', 'expand across', 'ongoing collaboration', 'ongoing role', 'future projects']]),

            // Negative rules
            $score('excluded_tech', 'Excluded technology required', 'technical_fit', -12, 'Part of the job needs a capability Code4Each has excluded.', ['op' => 'has_tier', 'value' => 'excluded']),
            $score('wishlist_posting', 'Wish-list posting', 'technical_fit', -6, 'The posting lists 20+ technologies; the real scope is unclear.', ['field' => 'job.technology_count', 'op' => 'gte', 'value' => 20]),
            $score('low_hourly', 'Hourly below minimum', 'commercial_value', -10, 'Hourly maximum is below the configured minimum.', ['field' => 'job.hourly_max', 'op' => 'lt', 'value' => ['setting' => 'min_hourly']]),
            $score('low_fixed', 'Fixed price below threshold', 'commercial_value', -10, 'Fixed budget is below the configured minimum.', ['all' => [
                ['field' => 'job.job_type', 'op' => 'eq', 'value' => 'fixed'],
                ['field' => 'job.fixed_budget', 'op' => 'lt', 'value' => ['setting' => 'min_fixed']],
            ]]),
            $score('cheap_wording', '"Cheap developer" wording', 'commercial_value', -10, 'The posting asks for cheap / low-cost work.', ['field' => 'job.text', 'op' => 'contains_any', 'value' => ['cheap developer', 'cheapest', 'low budget', 'lowest rate', 'very tight budget']]),
            $score('unverified_payment', 'Unverified payment', 'client_quality', -10, 'Client payment method is not verified.', ['field' => 'client.payment_verified', 'op' => 'is_false']),
            $score('zero_hire_rate', '0% hire rate', 'client_quality', -15, 'Client has posted several jobs but never hired.', ['all' => [
                ['field' => 'client.hire_rate', 'op' => 'eq', 'value' => 0],
                ['field' => 'client.jobs_posted', 'op' => 'gte', 'value' => 3], // a first-time client is not penalised
            ]]),
            $score('low_hire_rate', 'Hire rate below 20%', 'client_quality', -15, 'Client hires for fewer than 20% of postings.', ['all' => [
                ['field' => 'client.hire_rate', 'op' => 'lt', 'value' => 20],
                ['field' => 'client.hire_rate', 'op' => 'gt', 'value' => 0],
                ['field' => 'client.jobs_posted', 'op' => 'gte', 'value' => 5],
            ]]),

            // AI judgement (only when an analysis passed the confidence gate). Small on
            // purpose: verified facts above carry the score, AI nudges it.
            $score('ai_strong_fit', 'AI: strong fit', 'technical_fit', 4, 'AI analysis rates the fit 80+ (estimate).', ['all' => [
                ['field' => 'ai.available', 'op' => 'is_true'], ['field' => 'ai.fit_score', 'op' => 'gte', 'value' => 80],
            ]]),
            $score('ai_weak_fit', 'AI: weak fit', 'technical_fit', -6, 'AI analysis rates the fit below 40 (estimate).', ['all' => [
                ['field' => 'ai.available', 'op' => 'is_true'], ['field' => 'ai.fit_score', 'op' => 'lt', 'value' => 40],
            ]]),
            $score('ai_unrealistic_budget', 'AI: budget looks unrealistic', 'commercial_value', -4, 'AI judges the budget unrealistic for the scope (estimate).', ['all' => [
                ['field' => 'ai.available', 'op' => 'is_true'], ['field' => 'ai.budget_realistic', 'op' => 'eq', 'value' => 'no'],
            ]]),
            $score('ai_long_term_signal', 'AI: long-term potential', 'long_term', 3, 'AI sees ongoing work beyond this job (estimate).', ['all' => [
                ['field' => 'ai.available', 'op' => 'is_true'], ['field' => 'ai.long_term_potential', 'op' => 'eq', 'value' => 'yes'],
            ]]),

            // Classifications / archetypes
            $class('class_laravel', 'Laravel', 'LARAVEL', $tech(['laravel'])),
            $class('class_react', 'React', 'REACT', $tech(['react'])),
            $class('class_wordpress', 'WordPress', 'WORDPRESS', $tech(['wordpress', 'woocommerce'])),
            $class('class_saas', 'SaaS', 'SAAS', $tech(['saas'])),
            $class('class_crm', 'CRM', 'CRM', $tech(['crm', 'gohighlevel'])),
            $class('class_erp', 'ERP', 'ERP', $tech(['erp'])),
            $class('class_api', 'API Integration', 'API_INTEGRATION', $tech(['api-integration', 'webhooks'])),
            $class('class_legacy', 'Legacy Modernization', 'LEGACY_MODERNIZATION', $tech(['legacy-modernization'])),
            $class('class_ai', 'AI Automation', 'AI_AUTOMATION', $tech(['ai', 'openai', 'claude', 'gemini', 'llm', 'ai-agents', 'rag', 'document-ai', 'n8n', 'make', 'zapier', 'workflow-automation'])),
            $class('class_enterprise', 'Enterprise Integration', 'ENTERPRISE', ['any' => [
                $tech(['erp', 'entra-id']),
                ['all' => [
                    $tech(['crm']),
                    $tech(['api-integration']),
                    ['field' => 'client.total_spent', 'op' => 'gte', 'value' => 50000],
                ]],
            ]]),
            $class('class_quick_win', 'Quick Win', 'QUICK_WIN', ['all' => [
                ['field' => 'signals.scope_size', 'op' => 'eq', 'value' => 'small'],
                ['field' => 'scores.client_quality', 'op' => 'gte', 'value' => 60],
                ['field' => 'scores.technical_fit', 'op' => 'gte', 'value' => 70],
                ['any' => [
                    ['field' => 'job.hourly_max', 'op' => 'gte', 'value' => ['setting' => 'min_hourly']],
                    ['field' => 'job.fixed_budget', 'op' => 'between', 'value' => [500, 3000]],
                ]],
                ['field' => 'competition.proposals_min', 'op' => 'lt', 'value' => 20],
                ['any' => [
                    ['field' => 'signals.project_clarity', 'op' => 'is_null'],
                    ['field' => 'signals.project_clarity', 'op' => 'gte', 'value' => 0.5],
                ]],
            ]]),
            $class('class_strategic', 'Strategic', 'STRATEGIC', ['all' => [
                ['field' => 'scores.strategic', 'op' => 'gte', 'value' => ['setting' => 'strategic_min']],
                ['field' => 'job.duration_weeks', 'op' => 'gte', 'value' => ['setting' => 'strategic_min_weeks']],
                ['field' => 'scores.technical_fit', 'op' => 'gte', 'value' => 50],
            ]]),
            $class('class_returning_client', 'Returning client', 'RETURNING_CLIENT', ['field' => 'client.seen_before', 'op' => 'gte', 'value' => 1]),
            $class('class_previous_client', 'Previous Code4Each client', 'PREVIOUS_CLIENT', ['any' => [
                ['field' => 'client.previous_wins', 'op' => 'gte', 'value' => 1],
                ['field' => 'signals.previous_client', 'op' => 'is_true'],
            ]]),
            $class('class_long_term', 'Long-Term', 'LONG_TERM', ['field' => 'job.duration_weeks', 'op' => 'gte', 'value' => 24]),
            $class('class_high_value', 'High Value', 'HIGH_VALUE', ['any' => [
                ['field' => 'job.fixed_budget', 'op' => 'gte', 'value' => 10000],
                ['all' => [
                    ['field' => 'job.hourly_max', 'op' => 'gte', 'value' => 40],
                    ['field' => 'job.duration_weeks', 'op' => 'gte', 'value' => 12],
                ]],
            ]]),
        ];
    }

    /**
     * [code, label, kind, color, description]
     */
    private function classifications(): array
    {
        $a = 'archetype';
        $c = 'category';

        return [
            ['QUICK_WIN', 'Quick Win', $a, '#047857', 'Small, clear scope; strong client and fit; good budget; low competition.'],
            ['STRATEGIC', 'Strategic', $a, '#6d28d9', 'High Strategic Value: long engagement, strong client, platform work, repeat-hire potential.'],
            ['LONG_TERM', 'Long-Term', $a, '#1d4ed8', 'Six months or more of expected work.'],
            ['HIGH_VALUE', 'High Value', $a, '#b45309', 'Large fixed budget, or a good rate over a long engagement.'],
            ['AI_AUTOMATION', 'AI Automation', $a, '#be185d', 'AI, LLM, agent or workflow-automation work.'],
            ['ENTERPRISE', 'Enterprise Integration', $a, '#374151', 'ERP / identity work, or CRM integration for a high-spend client.'],
            ['LEGACY_MODERNIZATION', 'Legacy Modernization', $a, '#92400e', 'Modernising or refactoring an existing system.'],
            ['API_INTEGRATION', 'API Integration', $a, '#0e7490', 'Third-party API, webhook or data-sync work.'],
            ['RETURNING_CLIENT', 'Returning Client', $a, '#4338ca', 'The PMS has seen jobs from this client before.'],
            ['PREVIOUS_CLIENT', 'Previous Client', $a, '#15803d', 'Code4Each has won work with this client, or Upwork says it is a previous client.'],
            ['LARAVEL', 'Laravel', $c, '#dc2626', null],
            ['REACT', 'React', $c, '#0284c7', null],
            ['WORDPRESS', 'WordPress', $c, '#1e40af', null],
            ['SAAS', 'SaaS', $c, '#7c3aed', null],
            ['CRM', 'CRM', $c, '#0f766e', null],
            ['ERP', 'ERP', $c, '#475569', null],
        ];
    }

    private function seedClassifications(): void
    {
        $surface = ['QUICK_WIN', 'STRATEGIC', 'PREVIOUS_CLIENT'];

        foreach ($this->classifications() as $i => [$code, $label, $kind, $color, $description]) {
            UpworkClassification::firstOrCreate(['code' => $code], [
                'label' => $label, 'kind' => $kind, 'color' => $color, 'description' => $description, 'sort' => ($i + 1) * 10,
                'surface' => in_array($code, $surface, true),
            ]);
        }
    }

    private function seedSettings(): void
    {
        foreach (UpworkSetting::DEFAULTS as $key => $value) {
            if (!UpworkSetting::where('key', $key)->exists()) {
                UpworkSetting::set($key, $value);
            }
        }
    }
}
