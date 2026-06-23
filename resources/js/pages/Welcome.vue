<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import Lenis from 'lenis';
import {
  GraduationCap,
  Calendar,
  BookOpen,
  Users,
  Briefcase,
  CheckCircle2,
  ChevronRight,
  ArrowRight,
  ShieldCheck,
  Zap,
  Database,
  Globe,
  Smartphone,
  BarChart3,
  MessageSquare,
  ChevronDown,
  ChevronUp,
} from 'lucide-vue-next';
import { onMounted, onUnmounted, ref } from 'vue';
import ImpersonateBanner from '@/components/ImpersonateBanner.vue';
import KoamishinLogo from '@/components/KoamishinLogo.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card';

defineProps<{
  canRegister?: boolean;
  laravelVersion?: string;
  phpVersion?: string;
}>();

// Tech stack languages/technologies for carousel
const techStack = [
  'Laravel 12',
  'Vue 3',
  'TypeScript',
  'Tailwind CSS',
  'Inertia.js',
  'MySQL',
  'PHP 8.3',
  'Vite',
  'Taro',
  'React',
  'Node.js',
  'PostgreSQL',
];

const programs = [
  {
    title: 'Student Information System',
    description:
      'Comprehensive student management with enrollment, records, and attendance tracking.',
    icon: BookOpen,
    details: [
      'Enrollment Management',
      'Academic Records',
      'Attendance Tracking',
      'Gradebook',
    ],
  },
  {
    title: 'Learning Management',
    description:
      'Modern LMS for online and hybrid learning with assignments and assessments.',
    icon: GraduationCap,
    details: [
      'Course Management',
      'Online Assessments',
      'Resource Library',
      'Assignment Submission',
    ],
  },
  {
    title: 'Communication Hub',
    description:
      'Seamless communication between teachers, students, and parents.',
    icon: MessageSquare,
    details: [
      'Parent Portals',
      'Teacher-Student Chat',
      'Announcements',
      'Event Notifications',
    ],
  },
  {
    title: 'Analytics & Reporting',
    description:
      'Real-time data and comprehensive reports for data-driven decisions.',
    icon: BarChart3,
    details: [
      'Student Performance',
      'Attendance Reports',
      'Enrollment Analytics',
      'Custom Dashboards',
    ],
  },
];

const whyChoose = [
  {
    title: 'Modern Technology Stack',
    description:
      'Built on Laravel 12 and Vue 3, ensuring scalability, security, and performance.',
    icon: Zap,
  },
  {
    title: 'Enterprise-Grade Security',
    description:
      'Fully compliant with FERPA and other privacy regulations, keeping student data safe.',
    icon: ShieldCheck,
  },
  {
    title: 'Mobile-First Design',
    description:
      'Perfectly responsive on any device, from desktop to smartphones.',
    icon: Smartphone,
  },
  {
    title: 'Cloud-Ready Architecture',
    description:
      'Deployable anywhere with multi-tenant support for large school districts.',
    icon: Database,
  },
  {
    title: 'Multilingual Support',
    description:
      'Full i18n support for all major languages used in educational institutions.',
    icon: Globe,
  },
  {
    title: 'Customizable & Extensible',
    description:
      'Highly modular architecture allows easy customization for your specific needs.',
    icon: CheckCircle2,
  },
];

const faqs = [
  {
    question: 'What makes Koamishin Academy different from other SIS platforms?',
    answer:
      'We focus on a modern, student-first experience combined with enterprise-grade security. Our platform is built on the latest technology and designed for scalability.',
  },
  {
    question: 'Can we integrate Koamishin with our existing systems?',
    answer:
      'Yes! Our API-first architecture allows seamless integration with your current systems including HR, finance, and other educational tools.',
  },
  {
    question: 'How long does implementation take?',
    answer:
      'Implementation time varies by size, but most schools are up and running within 4-6 weeks. We provide full onboarding and training.',
  },
  {
    question: 'Is Koamishin suitable for all school sizes?',
    answer:
      'Absolutely! From small private schools to large public districts, our platform scales perfectly with your institution.',
  },
  {
    question: 'What kind of support do you provide?',
    answer:
      'We offer 24/7 technical support, comprehensive documentation, and regular updates to ensure your platform stays current.',
  },
  {
    question: 'Can parents and guardians access the platform?',
    answer:
      'Yes! We provide dedicated parent portals for viewing grades, attendance, and communicating with teachers.',
  },
];

const openFaq = ref<number | null>(null);

let lenisInstance: Lenis | null = null;
onMounted(() => {
  lenisInstance = new Lenis({
    duration: 1.2,
    easing: (t: number) => Math.min(1, 1.001 - Math.pow(2, -10 * t)),
    smoothWheel: true,
  });

  const raf = (time: number) => {
    if (lenisInstance) lenisInstance.raf(time);
    requestAnimationFrame(raf);
  };
  requestAnimationFrame(raf);

  // Intersection Observer for reveal animations
  const observer = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          entry.target.classList.add('revealed');
        }
      });
    },
    {
      threshold: 0.1,
    }
  );

  document.querySelectorAll('.reveal-element').forEach((el) => {
    observer.observe(el);
  });
});

onUnmounted(() => {
  if (lenisInstance) lenisInstance.destroy();
});

const toggleFaq = (index: number) => {
  openFaq.value = openFaq.value === index ? null : index;
};
</script>

<template>
  <Head title="Koamishin Academy - Modern School Management" />
  <div
    class="flex min-h-screen flex-col bg-background text-foreground selection:bg-primary selection:text-primary-foreground"
  >
    <ImpersonateBanner />
    <!-- Navbar -->
    <header
      class="sticky top-0 z-50 w-full border-b border-border/40 bg-background/95 backdrop-blur-sm supports-backdrop-filter:bg-background/60"
    >
      <div
        class="container mx-auto flex h-16 max-w-7xl items-center justify-between px-6"
      >
        <a href="#hero" class="flex items-center gap-2">
          <KoamishinLogo class="h-8 w-8" />
          <span class="text-lg font-bold">Koamishin</span>
        </a>

        <nav class="hidden items-center gap-6 md:flex">
          <a href="#programs" class="text-sm font-medium hover:text-primary">Features</a>
          <a href="#why" class="text-sm font-medium hover:text-primary">Why Koamishin</a>
          <a href="#faq" class="text-sm font-medium hover:text-primary">FAQ</a>
          <a href="#contact" class="text-sm font-medium hover:text-primary">Contact</a>
        </nav>

        <nav class="flex items-center gap-3">
          <template v-if="$page.props.auth.user">
            <Button as-child variant="ghost" size="sm">
              <Link :href="route('dashboard')">Dashboard</Link>
            </Button>
          </template>
          <template v-else>
            <Button as-child variant="ghost" size="sm">
              <Link :href="route('login')">Log in</Link>
            </Button>
            <Button as-child size="sm">
              <Link :href="route('register')">Get Started</Link>
            </Button>
          </template>
        </nav>
      </div>
    </header>

    <main class="flex-1">
      <!-- Hero Section -->
      <section id="hero" class="relative overflow-hidden py-24 lg:py-32">
        <div
          class="container mx-auto flex max-w-7xl flex-col items-center gap-8 px-6 text-center reveal-element"
        >
          <Badge variant="secondary" class="rounded-full px-4 py-1 text-sm">
            The Modern School Management Platform
          </Badge>

          <div class="flex flex-col items-center gap-4">
            <KoamishinLogo
              class="mb-4 h-32 w-32 rounded-[20px] shadow-2xl shadow-primary/20"
            />
            <h1
              class="bg-gradient-to-br from-foreground to-muted-foreground bg-clip-text text-4xl font-bold tracking-tighter text-transparent sm:text-6xl md:text-7xl lg:text-8xl"
            >
              Koamishin Academy
            </h1>
          </div>

          <p
            class="max-w-[52rem] text-lg leading-relaxed text-muted-foreground sm:text-xl sm:leading-8"
          >
            The complete school management platform that brings together students,
            teachers, and administrators into one seamless experience.
          </p>

          <div class="flex flex-wrap items-center justify-center gap-4 pt-6">
            <Button as-child size="lg" class="h-12 px-10">
              <Link :href="route('register')">Start Free Trial</Link>
            </Button>
            <Button as-child variant="outline" size="lg" class="h-12 px-10">
              <a href="#programs">Explore Features</a>
            </Button>
          </div>
        </div>
      </section>

      <!-- Features Section -->
      <section id="programs" class="container mx-auto max-w-7xl px-6 py-24">
        <div class="mb-16 text-center reveal-element">
          <h2
            class="mb-4 text-3xl font-bold tracking-tighter sm:text-4xl md:text-5xl"
          >
            Powerful Features for Schools
          </h2>
          <p class="mx-auto max-w-2xl text-lg text-muted-foreground">
            Everything you need to manage your institution efficiently and effectively
          </p>
        </div>

        <div class="grid gap-8 md:grid-cols-2 lg:grid-cols-4">
          <Card
            v-for="(feature, index) in programs"
            :key="feature.title"
            class="group border-border bg-card transition-all duration-500 hover:-translate-y-2 hover:border-primary/50 hover:shadow-xl reveal-element"
            :style="{ animationDelay: `${index * 100}ms` }"
          >
            <CardHeader>
              <div
                class="mb-4 flex h-14 w-14 items-center justify-center rounded-xl bg-primary/10 transition-colors group-hover:bg-primary/20"
              >
                <component :is="feature.icon" class="h-7 w-7 text-primary" />
              </div>
              <CardTitle class="text-xl font-semibold">{{ feature.title }}</CardTitle>
            </CardHeader>
            <CardContent>
              <CardDescription class="mb-6 text-sm">
                {{ feature.description }}
              </CardDescription>
              <ul class="space-y-2">
                <li
                  v-for="(detail, i) in feature.details"
                  :key="i"
                  class="flex items-center gap-2 text-sm text-muted-foreground"
                >
                  <CheckCircle2 class="h-4 w-4 text-emerald-500" />
                  {{ detail }}
                </li>
              </ul>
            </CardContent>
          </Card>
        </div>
      </section>

      <!-- Why Choose Section -->
      <section
        id="why"
        class="relative overflow-hidden bg-primary/5 py-24 lg:py-32"
      >
        <div class="container mx-auto max-w-7xl px-6">
          <div class="mb-16 text-center reveal-element">
            <h2
              class="mb-4 text-3xl font-bold tracking-tighter sm:text-4xl md:text-5xl"
            >
              Why Choose Koamishin?
            </h2>
            <p class="mx-auto max-w-2xl text-lg text-muted-foreground">
              Here's why schools around the world trust us with their management needs
            </p>
          </div>

          <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
            <Card
              v-for="(item, index) in whyChoose"
              :key="item.title"
              class="border-border bg-card reveal-element"
              :style="{ animationDelay: `${index * 75}ms` }"
            >
              <CardHeader>
                <div class="flex items-center gap-3">
                  <component :is="item.icon" class="h-8 w-8 text-primary" />
                  <CardTitle class="text-xl">{{ item.title }}</CardTitle>
                </div>
              </CardHeader>
              <CardContent>
                <CardDescription class="text-sm">{{ item.description }}</CardDescription>
              </CardContent>
            </Card>
          </div>
        </div>
      </section>

      <!-- FAQ Section -->
      <section id="faq" class="container mx-auto max-w-5xl px-6 py-24">
        <div class="mb-12 text-center reveal-element">
          <h2
            class="mb-4 text-3xl font-bold tracking-tighter sm:text-4xl md:text-5xl"
          >
            Frequently Asked Questions
          </h2>
          <p class="mx-auto max-w-2xl text-lg text-muted-foreground">
            Have questions? We've got answers
          </p>
        </div>

        <div class="space-y-4">
          <Card
            v-for="(faq, index) in faqs"
            :key="index"
            class="border-border bg-card reveal-element"
            :style="{ animationDelay: `${index * 50}ms` }"
          >
            <button
              @click="toggleFaq(index)"
              class="flex w-full items-center justify-between gap-4 px-6 py-5 text-left"
            >
              <span class="text-lg font-semibold">{{ faq.question }}</span>
              <ChevronDown
                v-if="openFaq !== index"
                class="h-6 w-6 text-muted-foreground"
              />
              <ChevronUp
                v-else
                class="h-6 w-6 text-muted-foreground"
              />
            </button>
            <div
              v-show="openFaq === index"
              class="px-6 pb-5 pt-0 text-muted-foreground transition-all duration-300"
            >
              {{ faq.answer }}
            </div>
          </Card>
        </div>
      </section>

      <!-- CTA Section -->
      <section
        id="contact"
        class="relative overflow-hidden bg-gradient-to-br from-primary/10 to-accent/10 py-24 lg:py-32"
      >
        <div class="container mx-auto max-w-5xl px-6 text-center reveal-element">
          <h2
            class="mb-6 text-4xl font-bold tracking-tighter sm:text-5xl md:text-6xl"
          >
            Ready to Transform Your School?
          </h2>
          <p class="mx-auto mb-10 max-w-2xl text-lg text-muted-foreground">
            Join hundreds of schools already using Koamishin to manage their daily operations
          </p>
          <div class="flex flex-wrap items-center justify-center gap-4">
            <Button as-child size="lg" class="h-12 px-10">
              <Link :href="route('register')">Get Started Now</Link>
            </Button>
            <Button as-child variant="outline" size="lg" class="h-12 px-10">
              <a href="mailto:contact@koamishin.com">Contact Sales</a>
            </Button>
          </div>
        </div>
      </section>

      <!-- Tech Stack Carousel -->
      <section
        class="relative border-y border-border bg-accent/30 py-10 overflow-hidden"
      >
        <div class="relative">
          <div 
            class="flex gap-16 px-8 animate-scroll"
          >
            <span
              v-for="(tech, index) in [...techStack, ...techStack]"
              :key="index"
              class="text-2xl font-bold uppercase tracking-widest text-muted-foreground/30 sm:text-3xl flex-shrink-0"
            >
              {{ tech }}
            </span>
          </div>
        </div>
      </section>

      <style>
        @keyframes scroll {
          0% {
            transform: translateX(0);
          }
          100% {
            transform: translateX(-50%);
          }
        }

        .animate-scroll {
          animation: scroll 30s linear infinite;
          display: inline-flex;
        }
      </style>
    </main>

    <!-- Footer -->
    <footer class="border-t border-border bg-card py-12">
      <div class="container mx-auto max-w-7xl px-6">
        <div class="grid gap-8 md:grid-cols-4">
          <div class="space-y-4">
            <div class="flex items-center gap-2">
              <KoamishinLogo class="h-8 w-8" />
              <span class="text-lg font-bold">Koamishin</span>
            </div>
            <p class="text-sm text-muted-foreground">
              Modern school management software for the next generation of education.
            </p>
          </div>

          <div class="space-y-4">
            <h4 class="font-semibold">Product</h4>
            <ul class="space-y-2 text-sm text-muted-foreground">
              <li><a href="#programs" class="hover:text-primary">Features</a></li>
              <li><a href="#why" class="hover:text-primary">Why Choose Us</a></li>
              <li><a href="#faq" class="hover:text-primary">FAQ</a></li>
            </ul>
          </div>

          <div class="space-y-4">
            <h4 class="font-semibold">Company</h4>
            <ul class="space-y-2 text-sm text-muted-foreground">
              <li><a href="#" class="hover:text-primary">About Us</a></li>
              <li><a href="#" class="hover:text-primary">Blog</a></li>
              <li><a href="#" class="hover:text-primary">Careers</a></li>
            </ul>
          </div>

          <div class="space-y-4">
            <h4 class="font-semibold">Legal</h4>
            <ul class="space-y-2 text-sm text-muted-foreground">
              <li><a href="#" class="hover:text-primary">Privacy Policy</a></li>
              <li><a href="#" class="hover:text-primary">Terms of Service</a></li>
              <li><a href="#" class="hover:text-primary">Security</a></li>
            </ul>
          </div>
        </div>

        <div class="mt-12 border-t border-border pt-8 text-center text-sm text-muted-foreground">
          <p>© {{ new Date().getFullYear() }} Koamishin. All rights reserved.</p>
        </div>
      </div>
    </footer>
  </div>
</template>
