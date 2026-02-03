import Image from 'next/image';
import { notFound } from 'next/navigation';

type Post = {
  slug: string;
  title: string;
  excerpt: string;
  content: string[];
  coverImage: string;
  date: string;
  author: string;
};

const posts: Post[] = [
  {
    slug: 'minimalist-house',
    title: 'Минималистичный дом: как создать спокойное пространство',
    excerpt:
      'Разбираем ключевые принципы минимализма в архитектуре, планировке и выборе материалов.',
    content: [
      'Минимализм начинается с функций: оставляем только то, что действительно нужно.',
      'Чистые линии, естественные материалы и спокойная палитра помогают создать ощущение порядка.',
      'Свет и воздух — главные элементы, поэтому планировка должна быть открытой и логичной.',
    ],
    coverImage: '/images/blog/minimalist-house.jpg',
    date: '2026-02-03',
    author: 'Редакция Одноэтажники',
  },
];

function getPostBySlug(slug: string) {
  return posts.find((post) => post.slug === slug);
}

export default function BlogPostPage({
  params,
}: {
  params: { slug: string };
}) {
  const post = getPostBySlug(params.slug);

  if (!post) {
    notFound();
  }

  const jsonLd = {
    '@context': 'https://schema.org',
    '@type': 'Article',
    headline: post.title,
    description: post.excerpt,
    image: [post.coverImage],
    author: {
      '@type': 'Person',
      name: post.author,
    },
    datePublished: post.date,
    dateModified: post.date,
  };

  return (
    <article className="mx-auto grid max-w-3xl gap-6 px-6 py-12 text-slate-900">
      <script
        type="application/ld+json"
        dangerouslySetInnerHTML={{ __html: JSON.stringify(jsonLd) }}
      />
      <header className="grid gap-4">
        <div className="text-sm text-slate-500">
          <time dateTime={post.date}>
            {new Date(post.date).toLocaleDateString('ru-RU', {
              year: 'numeric',
              month: 'long',
              day: 'numeric',
            })}
          </time>
          <span className="mx-2">•</span>
          <span>{post.author}</span>
        </div>
        <h1 className="text-3xl font-semibold tracking-tight">{post.title}</h1>
        <p className="text-lg text-slate-600">{post.excerpt}</p>
      </header>
      <div className="relative h-64 w-full overflow-hidden rounded-2xl bg-slate-100 shadow-sm">
        <Image
          src={post.coverImage}
          alt={post.title}
          fill
          priority
          className="object-cover"
          sizes="(max-width: 768px) 100vw, 768px"
        />
      </div>
      <section className="grid gap-4 text-base leading-relaxed text-slate-700">
        {post.content.map((paragraph) => (
          <p key={paragraph}>{paragraph}</p>
        ))}
      </section>
    </article>
  );
}
