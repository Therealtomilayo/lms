import {
  createIcons as baseCreateIcons,
  ArrowLeft,
  ArrowRight,
  ArrowUpRight,
  BookMarked,
  BookOpen,
  Brain,
  BrainCircuit,
  Bus,
  CalendarDays,
  Check,
  CheckCircle2,
  ChevronDown,
  ChevronLeft,
  ChevronRight,
  Clock,
  Cpu,
  Dumbbell,
  Eye,
  EyeOff,
  File,
  Flag,
  FlaskConical,
  GraduationCap,
  Home,
  Landmark,
  Lock,
  MapPin,
  Megaphone,
  Menu,
  Monitor,
  Mouse,
  Music,
  Palette,
  Phone,
  Quote,
  School,
  Send,
  ShieldCheck,
  Smile,
  Star,
  Trophy,
  UploadCloud,
  Users,
  X
} from 'lucide';

const icons = {
  ArrowLeft,
  ArrowRight,
  ArrowUpRight,
  BookMarked,
  BookOpen,
  Brain,
  BrainCircuit,
  Bus,
  CalendarDays,
  Check,
  CheckCircle2,
  ChevronDown,
  ChevronLeft,
  ChevronRight,
  Clock,
  Cpu,
  Dumbbell,
  Eye,
  EyeOff,
  File,
  Flag,
  FlaskConical,
  GraduationCap,
  Home,
  Landmark,
  Lock,
  MapPin,
  Megaphone,
  Menu,
  Monitor,
  Mouse,
  Music,
  Palette,
  Phone,
  Quote,
  School,
  Send,
  ShieldCheck,
  Smile,
  Star,
  Trophy,
  UploadCloud,
  Users,
  X
};

export function createIcons(options = {}) {
  return baseCreateIcons({
    icons,
    ...options
  });
}

if (typeof window !== 'undefined') {
  window.lucide = {
    createIcons,
    icons
  };
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => createIcons());
  } else {
    createIcons();
  }
}
